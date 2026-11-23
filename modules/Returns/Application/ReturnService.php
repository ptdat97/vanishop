<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Ordering\Contracts\Data\OrderLineData;
use Modules\Ordering\Contracts\Data\OrderSettings;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Payment\Contracts\Payments;
use Modules\Returns\Contracts\Data\ReturnContext;
use Modules\Returns\Contracts\Data\ReturnView;
use Modules\Returns\Contracts\ReturnPolicy;
use Modules\Returns\Contracts\ReturnRejected;
use Modules\Returns\Contracts\Returns;
use Modules\Returns\Domain\RefundCalculator;
use Modules\Returns\Domain\ReturnCondition;
use Modules\Returns\Domain\ReturnLimits;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Events\ReturnRequested;
use Modules\Returns\Events\ReturnResolved;
use Modules\Returns\Persistence\Models\ReturnAttachment;
use Modules\Returns\Persistence\Models\ReturnLine;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\StoreClock;
use Modules\Tenancy\Contracts\Settings;

/**
 * Service contract `Returns` (docs/09-order/order.md §7). Luật số lượng/tiền/tình trạng ở `Domain/` (architecture-
 * consolidation D2); sổ sự kiện + đồng bộ đơn + view ở `ReturnRecords`; đổi hàng ở `ExchangeResolution`.
 *
 * Invariant: tổng số lượng trả (yêu cầu còn mở + đã hoàn tất) của một dòng ≤ số đã giao — khoá dòng đơn khi tạo.
 * Tiền hoàn tính từ thành tiền dòng đã phân bổ giảm giá (không hoàn phí giao). Hoàn tiền qua Payment (≤ đã thu).
 */
final class ReturnService implements Returns
{
    /** Số ngày giữ ảnh đổi trả sau khi yêu cầu đóng (Admin → Cài đặt → Đơn hàng & đổi trả). */
    public const ATTACHMENTS_RETENTION_DAYS = 'returns.attachments_retention_days';

    public const SHIPPING_PAYER_CUSTOMER = 'customer';

    public const SHIPPING_PAYER_STORE = 'store';

    public function __construct(
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly InventoryReturns $inventory,
        private readonly Payments $payments,
        private readonly AuditLogger $audit,
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly VariantDirectory $variants,
        private readonly ReturnReasons $reasons,
        private readonly ReturnRecords $records,
        private readonly ExchangeResolution $exchange,
    ) {}

    public function request(int $orderId, array $lines, string $reasonCode, ?string $note, string $source, array $exchanges = []): ReturnView
    {
        $lines = array_filter($lines, fn (int $quantity): bool => $quantity > 0);
        if ($lines === []) {
            throw ReturnRejected::quantityExceeded(0, 0);
        }
        // Lý do phải có trong danh mục và đang bật (§3.13-1) — cho mọi kênh: web, API, Admin.
        if (! $this->reasons->isActive($reasonCode)) {
            throw ReturnRejected::reasonInvalid($reasonCode);
        }
        $exchanges = array_intersect_key($exchanges, $lines);
        if ($exchanges !== [] && count($exchanges) !== count($lines)) {
            throw ReturnRejected::exchangeInvalid('lines');
        }
        if ($exchanges !== []) {
            $variants = $this->variants->find(array_values(array_unique($exchanges)));
            foreach ($exchanges as $variantId) {
                if (($variants[$variantId] ?? null)?->status !== 'active') {
                    throw ReturnRejected::exchangeInvalid('variant');
                }
            }
        }

        $return = DB::transaction(function () use ($orderId, $lines, $reasonCode, $note, $source, $exchanges): ReturnRequest {
            $this->transitions->lock($orderId);
            $order = $this->orders->find($orderId) ?? throw ReturnRejected::notEligible('not_delivered');
            // Khách đã xác nhận nhận hàng và chọn không đổi trả (§3.11-1) — kể cả nhân viên tạo hộ.
            if ((bool) ($order->meta['returns_waived'] ?? false)) {
                throw ReturnRejected::notEligible('waived');
            }
            if ($order->source === 'exchange') {
                $this->exchange->guardReplacementOrder($orderId, $lines, $exchanges);
            }
            [$delivered, $deliveredAt] = $this->records->delivered($orderId);

            $decision = $this->policy()->evaluate(new ReturnContext($orderId, $lines, $reasonCode, $deliveredAt, now()->toDateTimeImmutable(), $source));
            if (! $decision->eligible) {
                throw ReturnRejected::notEligible((string) $decision->reason);
            }

            $orderLines = collect($this->orders->lines($orderId))->keyBy('id');
            $taken = $this->records->takenQuantities($orderId);
            $count = ReturnRequest::query()->where('order_id', $orderId)->count();

            $return = ReturnRequest::query()->create([
                'public_id' => (string) Str::ulid(), 'number' => $order->number.'-R'.($count + 1), 'order_id' => $orderId,
                'status' => ReturnStatus::Requested, 'reason_code' => $reasonCode, 'resolution' => $exchanges === [] ? 'refund' : 'exchange', 'customer_note' => $note === null ? null : mb_substr(trim($note), 0, 500),
                'source' => $source, 'refund_amount' => 0, 'currency_code' => $order->currencyCode,
            ]);

            $exceeded = ReturnLimits::firstExceeded($lines, $delivered, $taken);
            if ($exceeded !== null) {
                throw ReturnRejected::quantityExceeded(...$exceeded);
            }

            $total = 0;
            foreach ($lines as $lineId => $quantity) {
                /** @var OrderLineData|null $line */
                $line = $orderLines->get($lineId) ?? throw ReturnRejected::quantityExceeded((int) $lineId, 0);

                $refund = RefundCalculator::forUnits(Money::of($line->totalAmount, $order->currencyCode), $line->quantity, $taken[$lineId] ?? 0, $quantity);
                ReturnLine::query()->create([
                    'return_request_id' => $return->id, 'order_line_id' => $lineId, 'variant_id' => $line->variantId, 'exchange_variant_id' => $exchanges[$lineId] ?? null,
                    'quantity' => $quantity, 'refund_amount' => $refund->amount,
                ]);
                $total += $refund->amount;
            }

            $return->update(['refund_amount' => $total]);
            $this->records->event($return, null, ReturnStatus::Requested, $reasonCode, $source);
            // Tự duyệt của chính sách plugin cũng qua hook chặn (EP v3 X2); bị chặn → chờ CSKH.
            if ($decision->autoApprove && $this->records->approveBlockers($return) === []) {
                $this->records->move($return, ReturnStatus::Approved, 'auto_approved', 'system');
            }
            $this->records->syncOrder($orderId);
            event(new ReturnRequested($return->id, $orderId, $return->number, $source, $return->public_id));

            return $return;
        });

        return $this->records->view($return->fresh());
    }

    public function forOrder(int $orderId): array
    {
        return ReturnRequest::query()->where('order_id', $orderId)->orderBy('id')->get()->map(fn (ReturnRequest $return): ReturnView => $this->records->view($return))->all();
    }

    public function returnable(int $orderId): array
    {
        if ((bool) ($this->orders->find($orderId)?->meta['returns_waived'] ?? false)) {
            return ['lines' => [], 'deadline' => null];
        }
        [$delivered, $deliveredAt] = $this->records->delivered($orderId);
        $taken = $this->records->takenQuantities($orderId);
        $lines = ReturnLimits::remaining($delivered, $taken);

        $days = (int) $this->settings->get('core', OrderSettings::RETURN_WINDOW_DAYS);

        return ['lines' => $lines, 'deadline' => StoreClock::iso($deliveredAt?->modify("+{$days} days"))];
    }

    public function reasons(string $locale): array
    {
        return $this->reasons->active($locale);
    }

    public function reasonLabel(string $code, string $locale): string
    {
        return $this->reasons->label($code, $locale);
    }

    public function markShipped(int $orderId, string $returnPublicId, ?string $trackingNumber, string $source, string $notePrefix = 'shipped_by_customer'): ReturnView
    {
        $return = ReturnRequest::query()->where('order_id', $orderId)->where('public_id', $returnPublicId)->firstOrFail();
        $tracking = $trackingNumber === null || trim($trackingNumber) === '' ? null : mb_substr(trim($trackingNumber), 0, 64);

        DB::transaction(function () use ($return, $tracking, $source, $notePrefix): void {
            $locked = ReturnRequest::query()->whereKey($return->id)->lockForUpdate()->firstOrFail();
            if (! $locked->status->canMoveTo(ReturnStatus::InTransit)) {
                throw ReturnRejected::invalidTransition($locked->status->value, ReturnStatus::InTransit->value);
            }
            $locked->update(['return_tracking_number' => $tracking]);
            $this->records->move($locked, ReturnStatus::InTransit, $tracking === null ? $notePrefix : "{$notePrefix}: {$tracking}", $source);
            $this->records->syncOrder($locked->order_id);
            $this->audit->record('return.in_transit', 'return_request', $locked->id, ['number' => $locked->number, 'tracking' => $tracking, 'source' => $source]);
        });

        return $this->records->view($return->fresh());
    }

    public function attachPhotos(int $orderId, string $returnPublicId, array $photos, string $source): ReturnView
    {
        $return = ReturnRequest::query()->where('order_id', $orderId)->where('public_id', $returnPublicId)->firstOrFail();
        if (! $return->status->acceptsAttachments()) {
            throw ReturnRejected::attachmentsInvalid('closed');
        }
        foreach ($photos as $photo) {
            if (! $photo->isValid() || ! in_array($photo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true) || $photo->getSize() > 5 * 1024 * 1024) {
                throw ReturnRejected::attachmentsInvalid('invalid_file');
            }
        }
        if (ReturnAttachment::query()->where('return_request_id', $return->id)->count() + count($photos) > self::MAX_ATTACHMENTS) {
            throw ReturnRejected::attachmentsInvalid('too_many');
        }

        $disk = (string) config('vanishop.returns.attachments_disk', 'local');
        foreach ($photos as $photo) {
            $path = $photo->storeAs("returns/{$return->public_id}", Str::ulid().'.'.($photo->guessExtension() ?? 'jpg'), $disk);
            ReturnAttachment::query()->create([
                'return_request_id' => $return->id, 'disk' => $disk, 'path' => (string) $path, 'mime_type' => (string) $photo->getMimeType(),
                'size_bytes' => (int) $photo->getSize(), 'source' => $source, 'created_at' => now(),
            ]);
        }

        return $this->records->view($return->fresh());
    }

    public function cancel(int $orderId, string $returnPublicId, string $source): ReturnView
    {
        $return = ReturnRequest::query()->where('order_id', $orderId)->where('public_id', $returnPublicId)->firstOrFail();
        $this->transition($return->id, ReturnStatus::Cancelled, "cancelled_by_{$source}", $source);

        return $this->records->view($return->fresh());
    }

    /**
     * Thao tác nhân viên: duyệt, từ chối, đang gửi về.
     */
    public function transition(int $returnId, ReturnStatus $to, ?string $note, string $source, ?int $expectedLockVersion = null): void
    {
        DB::transaction(function () use ($returnId, $to, $note, $source, $expectedLockVersion): void {
            $return = ReturnRequest::query()->whereKey($returnId)->lockForUpdate()->firstOrFail();
            if ($expectedLockVersion !== null && $return->lock_version !== $expectedLockVersion) {
                throw ReturnRejected::stale();
            }
            if ($to === ReturnStatus::Cancelled && $source === 'customer' && ! $return->status->customerCancellable()) {
                throw ReturnRejected::invalidTransition($return->status->value, $to->value);
            }
            if ($to === ReturnStatus::Approved && ($blocked = $this->records->approveBlockers($return)) !== []) {
                throw ReturnRejected::approveBlocked($blocked);
            }

            $this->records->move($return, $to, $note, $source);
            $this->records->syncOrder($return->order_id);
            $this->audit->record("return.{$to->value}", 'return_request', $return->id, ['number' => $return->number, 'note' => $note]);
        });
    }

    /**
     * Nhận hàng trả: ghi tình trạng từng dòng; dòng bán được thì nhập lại kho (location của vận đơn đã giao).
     *
     * @param  array<int, string>  $conditions  return_line_id => sellable|damaged
     */
    public function receive(int $returnId, array $conditions, ?string $note, bool $requireAllConditions = false): void
    {
        DB::transaction(function () use ($returnId, $conditions, $note, $requireAllConditions): void {
            $return = ReturnRequest::query()->with('lines')->whereKey($returnId)->lockForUpdate()->firstOrFail();
            if (! $return->status->canMoveTo(ReturnStatus::Received)) {
                throw ReturnRejected::invalidTransition($return->status->value, ReturnStatus::Received->value);
            }

            // Khoá tình trạng = id dòng trả hàng. Khoá lạ bị từ chối: không âm thầm coi là "bán được" và nhập kho hàng hỏng.
            $unknown = array_diff(array_keys($conditions), $return->lines->pluck('id')->all());
            if ($unknown !== []) {
                throw ReturnRejected::unknownLines(array_values($unknown));
            }
            if ($requireAllConditions && count($conditions) !== $return->lines->count()) {
                throw ReturnRejected::stale();
            }

            $locations = $this->records->shipmentLocations($return->order_id);
            foreach ($return->lines as $line) {
                $condition = ReturnCondition::fromInput($conditions[$line->id] ?? null);
                $location = $locations[$line->order_line_id] ?? null;
                $line->update(['condition' => $condition->value, 'restock_location_id' => $condition->restocks() ? $location : null]);

                if ($condition->restocks() && $location !== null) {
                    $this->inventory->restock($location, $line->variant_id, $line->quantity, 'customer_return', "return:{$return->public_id}:{$line->id}");
                }
            }

            $this->records->move($return, ReturnStatus::Received, $note, 'staff');
            $this->records->syncOrder($return->order_id);
            $this->audit->record('return.received', 'return_request', $return->id, ['conditions' => $conditions]);
        });
    }

    /**
     * Hoàn tất: hoàn tiền (mặc định toàn bộ số tính được; có thể trừ phí hư hỏng) qua Payment. Yêu cầu đổi hàng →
     * resolveExchange().
     *
     * Phí ship trả hàng (L10): `$returnShippingFee` + `$returnShippingPayer` (`customer` | `store`). Khách chịu → tiền
     * hoàn thực tế = `$amount` − phí (không âm); cửa hàng chịu → không trừ. Chỉ ghi lại để báo cáo, không gọi hãng.
     */
    public function resolve(int $returnId, ?int $amount, ?string $note, int $returnShippingFee = 0, ?string $returnShippingPayer = null): void
    {
        $return = ReturnRequest::query()->findOrFail($returnId);
        if ($return->resolution === 'exchange') {
            $this->exchange->resolve($returnId, $note);

            return;
        }
        $amount ??= $return->refund_amount;
        if (! ReturnLimits::refundWithinLimit($amount, (int) $return->refund_amount)) {
            throw ReturnRejected::refundExceeds($return->refund_amount);
        }
        $returnShippingFee = max(0, $returnShippingFee);
        $returnShippingPayer = $returnShippingFee === 0 ? null : ($returnShippingPayer === self::SHIPPING_PAYER_CUSTOMER ? self::SHIPPING_PAYER_CUSTOMER : self::SHIPPING_PAYER_STORE);
        if ($returnShippingPayer === self::SHIPPING_PAYER_CUSTOMER) {
            $amount = max(0, $amount - $returnShippingFee);
        }

        DB::transaction(function () use ($returnId, $amount, $note, $returnShippingFee, $returnShippingPayer): void {
            $return = ReturnRequest::query()->whereKey($returnId)->lockForUpdate()->firstOrFail();
            if (! $return->status->canMoveTo(ReturnStatus::Resolved)) {
                throw ReturnRejected::invalidTransition($return->status->value, ReturnStatus::Resolved->value);
            }

            if ($amount > 0) {
                $this->payments->refundOrder($return->order_id, Money::of($amount, $return->currency_code), "return:{$return->number}", "return:{$return->public_id}");
            }

            $return->update(['refunded_amount' => $amount, 'resolved_at' => now(), 'return_shipping_fee' => $returnShippingFee, 'return_shipping_payer' => $returnShippingPayer]);
            $this->records->move($return, ReturnStatus::Resolved, $note, 'staff');
            $this->records->syncOrder($return->order_id);
            $this->audit->record('return.resolved', 'return_request', $return->id, ['refunded' => $amount] + ($returnShippingFee > 0 ? ['return_shipping_fee' => $returnShippingFee, 'return_shipping_payer' => $returnShippingPayer] : []));
            event(new ReturnResolved($return->id, $return->order_id, $amount, $return->public_id, $return->number));
        });
    }

    /**
     * @return array{lines: list<array{return_line_id: int, variant_id: int, sku: string, quantity: int, unit_amount: int, credit: int, same_style: bool}>, payable: int, refund: int}
     */
    public function exchangeQuote(int $returnId): array
    {
        return $this->exchange->exchangeQuote($returnId);
    }

    public function view(ReturnRequest $return): ReturnView
    {
        return $this->records->view($return);
    }

    /**
     * Chính sách đổi trả theo cấu hình `core.returns.policy` của cửa hàng (mặc định VANI_RETURN_POLICY).
     */
    private function policy(): ReturnPolicy
    {
        $default = (string) config('vanishop.returns.policy', 'days_window');
        $code = (string) $this->settings->get('core', 'returns.policy', $default);
        $policy = $this->extensions->select(ReturnPolicy::TAG, $code, $default);

        return $policy instanceof ReturnPolicy ? $policy : throw new \RuntimeException("ReturnPolicy [{$code}] chưa được đăng ký.");
    }
}
