<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartLineView;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\CheckoutRejected;
use Modules\Checkout\Contracts\CheckoutValidator;
use Modules\Checkout\Contracts\Data\Adjustment;
use Modules\Checkout\Contracts\Data\CheckoutIssue;
use Modules\Checkout\Contracts\Data\CheckoutQuote;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\PlaceOrderResult;
use Modules\Checkout\Contracts\Data\Totals;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Customer\Contracts\Customers;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Inventory\Contracts\Data\ReservationLine;
use Modules\Inventory\Contracts\Data\ReservationRequest;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Ordering\Contracts\Data\OrderAdjustmentDraft;
use Modules\Ordering\Contracts\Data\OrderDraft;
use Modules\Ordering\Contracts\Data\OrderLineDraft;
use Modules\Ordering\Contracts\Data\PlacedOrder;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Payment\Contracts\Data\PaymentView;
use Modules\Payment\Contracts\Payments;
use Modules\Promotion\Contracts\Data\PromotionResult;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Shared\Application\IdempotencyStore;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Phone\PhoneNumber;
use Throwable;

/**
 * PlaceOrder: docs/03-domains/cart-checkout.md §4. Một transaction: khoá giỏ → tính lại tổng → kiểm tra →
 * giữ hàng → tạo đơn → ghi nhận khuyến mãi → đóng giỏ → lưu phản hồi idempotency.
 */
final class CheckoutService implements Checkout
{
    /** @deprecated dùng {@see CheckoutValidator::TAG} (public API). */
    public const VALIDATORS_TAG = CheckoutValidator::TAG;

    public function __construct(
        private readonly Carts $carts,
        private readonly TotalsPipeline $pipeline,
        private readonly PaymentMethods $payments,
        private readonly ShippingOptions $shipping,
        private readonly InventoryReservation $inventory,
        private readonly OrderWriter $orders,
        private readonly PromotionEngine $promotions,
        private readonly IdempotencyStore $idempotency,
        private readonly Payments $paymentService,
        private readonly Extensions $extensions,
        private readonly Customers $customers,
        private readonly CurrentContext $currentContext,
    ) {}

    public function quote(CheckoutRequest $request): CheckoutQuote
    {
        $cart = $this->carts->view($request->cart);
        $context = $this->context($cart, $request, $this->signedInCustomer());
        $totals = $this->pipeline->run($context);

        return new CheckoutQuote($totals, $this->shipping->for($context), $this->payments->available($totals), $cart->isCheckoutReady());
    }

    public function placeOrder(CheckoutRequest $request, string $idempotencyKey): PlaceOrderResult
    {
        $scope = 'checkout:'.$request->cart->publicId;
        $stored = $this->idempotency->claim($scope, $idempotencyKey, $request->fingerprint());
        if ($stored !== null) {
            return new PlaceOrderResult($stored->status, $stored->body, replayed: true, payment: $this->paymentView($stored->body, replay: true));
        }

        try {
            $body = DB::transaction(function () use ($request, $scope, $idempotencyKey): array {
                $cart = $this->carts->lockForCheckout($request->cart);
                $customerId = $this->customerFor($request);
                $context = $this->context($cart, $request, $customerId);
                $totals = $this->pipeline->run($context);

                $this->validate($request, $totals, $cart->isCheckoutReady());
                if ($request->expectedTotal !== null && $request->expectedTotal !== $totals->grandTotal->amount) {
                    throw CheckoutRejected::totalsChanged($request->expectedTotal, $totals->grandTotal->amount, $totals->currencyCode);
                }

                $publicId = (string) Str::ulid();
                $reservationKey = "order:{$publicId}";
                // Giữ hàng TRƯỚC khi tạo đơn: yêu cầu hết hàng thoát sớm, không chạm khoá số đơn.
                // Giữ tới khi hết hạn thanh toán (+10 phút dự phòng); job hết hạn thanh toán huỷ đơn và nhả hàng trước.
                $ttl = $this->paymentService->paymentTtl((string) $request->paymentMethod);
                $this->inventory->reserve(new ReservationRequest(
                    $reservationKey,
                    $cart->channelId,
                    array_map(fn (TotalsLine $line): ReservationLine => new ReservationLine($line->variantId, $line->quantity), $totals->lines),
                    $ttl === null ? null : $ttl + 600,
                ));

                $placed = $this->orders->create($this->draft($publicId, $reservationKey, $cart, $request, $totals, $customerId, $this->orderMeta($request, $totals)));
                $payment = $this->paymentService->createForOrder($placed, (string) $request->paymentMethod);
                $this->promotions->recordUsage($placed->id, $customerId, $totals->currencyCode, $totals->promotions ?? new PromotionResult([], []));
                Hook::action('vani.order.after_create', $placed);
                $this->carts->markConverted($request->cart, $publicId);

                $body = $this->present($placed, $totals, $payment['public_id'], (string) $request->paymentMethod);
                $this->idempotency->complete($scope, $idempotencyKey, 201, $body);

                return $body;
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->idempotency->release($scope, $idempotencyKey);

            throw $exception;
        }

        return new PlaceOrderResult(201, $body, replayed: false, payment: $this->paymentView($body, replay: false));
    }

    private function signedInCustomer(): ?int
    {
        if (! $this->currentContext->has()) {
            return null;
        }
        $actor = $this->currentContext->actor();

        return $actor->type === ActorType::Customer ? $actor->id : null;
    }

    /**
     * Khách đã đăng nhập; không thì profile (ẩn) theo SĐT liên hệ — lịch sử đơn vãng lai về đúng khách khi
     * khách đăng ký bằng SĐT đó. SĐT sai → null (validator báo lỗi sau).
     */
    private function customerFor(CheckoutRequest $request): ?int
    {
        $signedIn = $this->signedInCustomer();
        if ($signedIn !== null) {
            return $signedIn;
        }

        $contact = (array) $request->contact;
        $phone = PhoneNumber::tryFromString((string) ($contact['phone'] ?? ''));
        if ($phone === null || trim((string) ($contact['full_name'] ?? '')) === '') {
            return null;
        }

        $email = trim((string) ($contact['email'] ?? ''));

        return $this->customers->resolveForCheckout($phone->e164, (string) $contact['full_name'], $email === '' ? null : $email);
    }

    private function context(CartView $cart, CheckoutRequest $request, ?int $customerId): TotalsContext
    {
        $lines = [];
        foreach ($cart->lines as $line) {
            if ($line->variant === null || $line->unitPrice === null || in_array(CartLineView::ISSUE_UNAVAILABLE, $line->issues, true)) {
                continue;
            }

            $lines[] = new TotalsLine(
                key: $line->variantId, variantId: $line->variantId, brandId: $line->variant->brandId, styleId: $line->variant->styleId,
                sku: $line->variant->sku, name: $line->variant->name, colorName: $line->variant->colorName, sizeCode: $line->variant->sizeCode,
                imageUrl: $line->variant->imageUrl, quantity: $line->quantity, unitPrice: $line->unitPrice, compareAt: $line->compareAt,
                subtotal: $line->unitPrice->multiply($line->quantity), discount: $line->unitPrice->multiply(0),
            );
        }

        return new TotalsContext(
            channelId: $cart->channelId,
            customerId: $customerId,
            currencyCode: $cart->currencyCode,
            lines: $lines,
            adjustments: [],
            voucherCodes: array_values(array_unique(array_filter(array_map('trim', $request->voucherCodes)))),
            shippingMethod: $request->shippingMethod,
            shippingAddress: $request->shippingAddress,
            now: now()->getTimestamp(),
        );
    }

    private function validate(CheckoutRequest $request, Totals $totals, bool $cartReady): void
    {
        $issues = array_map(fn (mixed $message): CheckoutIssue => new CheckoutIssue('rule', (string) $message), Hook::collect('vani.checkout.before_validate', $request));

        foreach ($this->extensions->tagged(self::VALIDATORS_TAG) as $validator) {
            if ($validator instanceof CheckoutValidator) {
                array_push($issues, ...$validator->validate($request, $totals, $cartReady));
            }
        }

        array_push($issues, ...array_map(fn (mixed $message): CheckoutIssue => new CheckoutIssue('rule', (string) $message), Hook::collect('vani.checkout.after_validate', $request, $totals)));

        if ($issues !== []) {
            throw CheckoutRejected::invalid($issues);
        }
        if ($totals->rejectedVouchers !== []) {
            throw CheckoutRejected::vouchers($totals->rejectedVouchers);
        }
    }

    /**
     * orders.meta do plugin bổ sung (vani.order.before_create); chỉ nhận mảng khoá chuỗi.
     *
     * @return array<string, mixed>
     */
    private function orderMeta(CheckoutRequest $request, Totals $totals): array
    {
        $meta = Hook::filter('vani.order.before_create', [], $request, $totals);

        return is_array($meta) ? array_filter($meta, fn (mixed $value, mixed $key): bool => is_string($key), ARRAY_FILTER_USE_BOTH) : [];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function draft(string $publicId, string $reservationKey, CartView $cart, CheckoutRequest $request, Totals $totals, ?int $customerId, array $meta = []): OrderDraft
    {
        $contact = (array) $request->contact;
        $email = trim((string) ($contact['email'] ?? ''));

        return new OrderDraft(
            publicId: $publicId,
            brandId: $totals->brandIds()[0],
            channelId: $cart->channelId,
            customerId: $customerId,
            currencyCode: $totals->currencyCode,
            paymentMethod: (string) $request->paymentMethod,
            paymentStatus: $this->payments->initialPaymentStatus((string) $request->paymentMethod),
            lines: array_map(fn (TotalsLine $line): OrderLineDraft => new OrderLineDraft(
                $line->variantId, $line->sku, $line->name, $line->colorName, $line->sizeCode, $line->imageUrl, $line->quantity,
                $line->unitPrice->amount, $line->compareAt?->amount, $line->subtotal->amount, $line->discount->amount, $line->total()->amount,
                $line->taxRateBp, $line->tax->amount ?? 0,
            ), $totals->lines),
            adjustments: array_map(fn (Adjustment $adjustment): OrderAdjustmentDraft => new OrderAdjustmentDraft(
                $adjustment->type, $adjustment->source, $adjustment->code, $adjustment->label, $adjustment->amount->amount, $adjustment->meta,
            ), $totals->adjustments),
            subtotalAmount: $totals->subtotal->amount,
            discountAmount: $totals->discount->amount,
            shippingAmount: $totals->shippingFee()->amount,
            taxAmount: $totals->tax->amount,
            totalAmount: $totals->grandTotal->amount,
            customer: [
                'full_name' => trim((string) $contact['full_name']),
                'phone' => PhoneNumber::fromString((string) $contact['phone'])->e164,
                'email' => $email === '' ? null : mb_strtolower($email),
            ],
            shippingAddress: array_map(fn (mixed $value): string => trim((string) $value), (array) $request->shippingAddress),
            shippingMethod: ['code' => $totals->shipping?->code, 'label' => $totals->shipping?->label, 'source' => $totals->shipping?->source, 'fee' => $totals->shippingFee()->amount],
            note: $request->note === null || trim($request->note) === '' ? null : trim($request->note),
            reservationKey: $reservationKey,
            sourceCartId: $cart->id,
            meta: $meta,
        );
    }

    /**
     * Sau commit: khởi tạo với cổng (idempotent). Không lưu vào phản hồi idempotency vì QR/URL có thể đổi;
     * gửi lại request → lấy lại hành động hiện tại của payment.
     *
     * @param  array<string, mixed>  $body
     */
    private function paymentView(array $body, bool $replay): ?PaymentView
    {
        $paymentId = $body['payment']['id'] ?? null;
        if (! is_string($paymentId)) {
            return null;
        }

        $view = $replay ? $this->paymentService->view($paymentId) : $this->paymentService->initiate($paymentId);
        if ($view !== null && $replay && $view->action === null && $view->status === 'pending') {
            $view = $this->paymentService->initiate($paymentId);
        }

        return $view;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(PlacedOrder $order, Totals $totals, string $paymentId, string $method): array
    {
        return [
            'payment' => ['id' => $paymentId, 'method' => $method],
            // Token xem/huỷ đơn không cần tài khoản (header X-Vani-Order-Token). Chỉ trả ở phản hồi đặt hàng.
            'access_token' => $order->accessToken,
            'id' => $order->publicId,
            'number' => $order->number,
            'order_status' => $order->orderStatus,
            'payment_status' => $order->paymentStatus,
            'total' => ['amount' => $order->totalAmount, 'currency' => $order->currencyCode],
            'item_count' => array_sum(array_map(fn (TotalsLine $line): int => $line->quantity, $totals->lines)),
        ];
    }
}
