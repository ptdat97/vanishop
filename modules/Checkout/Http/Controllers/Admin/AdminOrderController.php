<?php

declare(strict_types=1);

namespace Modules\Checkout\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Checkout\Application\AdminOrders;
use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Tạo đơn trong Admin (stabilization A8): chọn SKU + số lượng, thông tin người nhận, phí giao, thanh toán → tính tiền (cùng
 * luật checkout) → tạo đơn nguồn `admin`, mở trang đơn.
 */
final class AdminOrderController
{
    public function create(): Response
    {
        Gate::authorize('orders.manage');

        return Inertia::render('Checkout::AdminOrders/Create', [
            'idempotencyKey' => (string) Str::uuid(),
            'urls' => [
                'variants' => route('admin.orders.new.variants'),
                'quote' => route('admin.orders.new.quote'),
                'store' => route('admin.orders.new.store'),
                'orders' => route('admin.orders.orders.index'),
                'provinces' => url('/api/storefront/v1/address/provinces'),
            ],
        ]);
    }

    /**
     * Tìm SKU theo mã/tên (tối đa 20) để thêm vào đơn.
     */
    public function variants(Request $request, VariantDirectory $variants): JsonResponse
    {
        Gate::authorize('orders.manage');
        $text = trim((string) $request->validate(['q' => ['required', 'string', 'max:100']])['q']);

        return response()->json(['data' => array_map($this->variantRow(...), array_values(array_filter($variants->search($text, 40), fn ($variant): bool => $variant->status === 'active')))]);
    }

    public function resolveVariants(Request $request, VariantDirectory $variants): JsonResponse
    {
        Gate::authorize('orders.manage');
        $skus = $request->validate(['skus' => ['required', 'array', 'list', 'max:100'], 'skus.*' => ['required', 'string', 'max:100']])['skus'];
        $matches = array_filter($variants->findBySkus($skus), fn (VariantData $variant): bool => $variant->status === 'active');

        return response()->json(['data' => array_values(array_map($this->variantRow(...), $matches))]);
    }

    /** @return array{variant_id: int, sku: string, name: string, status: string} */
    private function variantRow(VariantData $variant): array
    {
        return [
            'variant_id' => $variant->id, 'sku' => $variant->sku, 'name' => trim($variant->productName.' '.VariantOptionsText::label($variant->options)), 'status' => $variant->status,
        ];
    }

    /**
     * Tính tiền: đồng bộ giỏ của form với các dòng rồi báo giá — trả khoá giỏ để lần sau dùng lại.
     */
    public function quote(Request $request, AdminOrders $orders, MoneyFormatter $money): JsonResponse
    {
        Gate::authorize('orders.manage');
        $data = $request->validate([
            ...$this->rules(required: false),
            'cart_id' => ['nullable', 'string', 'size:26'], 'cart_token' => ['nullable', 'string', 'max:128'],
        ]);
        $key = $orders->syncCart(isset($data['cart_id'], $data['cart_token']) ? new CartKey($data['cart_id'], $data['cart_token']) : null, $this->lines($data));
        $quote = $orders->quote($key, $this->contact($data), $this->address($data), $data['shipping_method'] ?? null, $data['payment_method'] ?? null, $data['note'] ?? null);
        $totals = $quote->totals;
        $format = fn ($amount): string => $money->format($amount);

        return response()->json([
            'cart' => ['id' => $key->publicId, 'token' => $key->token],
            'lines' => array_map(fn (TotalsLine $line): array => [
                'variant_id' => $line->variantId, 'sku' => $line->sku, 'quantity' => $line->quantity, 'unit_price' => $format($line->unitPrice), 'subtotal' => $format($line->subtotal),
            ], $totals->lines),
            'shipping_options' => array_map(fn (ShippingOption $option): array => ['code' => $option->code, 'label' => $option->label, 'fee' => $format($option->fee)], $quote->shippingOptions),
            'payment_methods' => $quote->paymentMethods,
            'subtotal' => $format($totals->subtotal), 'discount' => $format($totals->discount), 'shipping' => $format($totals->shippingFee()),
            'total' => $format($totals->grandTotal), 'total_amount' => $totals->grandTotal->amount, 'ready' => $quote->cartReady,
        ]);
    }

    public function store(Request $request, AdminOrders $orders): RedirectResponse
    {
        Gate::authorize('orders.manage');
        $data = $request->validate([
            ...$this->rules(required: true),
            'cart_id' => ['required', 'string', 'size:26'], 'cart_token' => ['required', 'string', 'max:128'],
            'expected_total' => ['required', 'integer', 'min:0'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $key = $orders->syncCart(new CartKey($data['cart_id'], $data['cart_token']), $this->lines($data));
        $orderId = $orders->place($key, (array) $this->contact($data), (array) $this->address($data), (string) $data['shipping_method'], (string) $data['payment_method'], $data['note'] ?? null, (int) $data['expected_total'], (string) $data['idempotency_key']);

        return redirect()->route('admin.orders.orders.show', $orderId)->with('success', __('checkout::messages.admin_order_created'));
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(bool $required): array
    {
        $need = $required ? 'required' : 'nullable';

        return [
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.variant_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'full_name' => [$need, 'string', 'max:120'], 'phone' => [$need, 'string', 'max:20'], 'email' => ['nullable', 'email', 'max:190'],
            'province_code' => [$need, 'string', 'max:16'], 'province_name' => ['nullable', 'string', 'max:120'],
            'ward_code' => [$need, 'string', 'max:16'], 'ward_name' => ['nullable', 'string', 'max:120'],
            'street_line' => [$need, 'string', 'max:255'],
            'shipping_method' => [$need, 'string', 'max:64'], 'payment_method' => [$need, 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    private function lines(array $data): array
    {
        $lines = [];
        foreach ((array) $data['lines'] as $line) {
            $lines[(int) $line['variant_id']] = ($lines[(int) $line['variant_id']] ?? 0) + (int) $line['quantity'];
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{full_name: string, phone: string, email: ?string}|null
     */
    private function contact(array $data): ?array
    {
        return ($data['full_name'] ?? '') === '' || ($data['phone'] ?? '') === '' ? null
            : ['full_name' => (string) $data['full_name'], 'phone' => (string) $data['phone'], 'email' => $data['email'] ?? null];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{province_code: string, province_name: string, ward_code: string, ward_name: string, street_line: string}|null
     */
    private function address(array $data): ?array
    {
        return ($data['province_code'] ?? '') === '' || ($data['ward_code'] ?? '') === '' || ($data['street_line'] ?? '') === '' ? null : [
            'province_code' => (string) $data['province_code'], 'province_name' => (string) ($data['province_name'] ?? ''),
            'ward_code' => (string) $data['ward_code'], 'ward_name' => (string) ($data['ward_name'] ?? ''), 'street_line' => (string) $data['street_line'],
        ];
    }
}
