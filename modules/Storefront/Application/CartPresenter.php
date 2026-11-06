<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Cart\Contracts\Data\CartLineView;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Định dạng giỏ cho Storefront API / native storefront (tiền có "formatted" theo locale VN).
 */
final class CartPresenter
{
    public function __construct(
        private readonly MoneyFormatter $money,
        private readonly Enrichment $enrichment,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(CartView $cart): array
    {
        return $this->enrichment->applyOne('cart', [
            'id' => $cart->id,
            'status' => $cart->status,
            'currency' => $cart->currencyCode,
            'item_count' => $cart->itemCount,
            'subtotal' => $this->money->toArray($cart->subtotal),
            // Phần sẽ đặt hàng (dòng đã chọn, 0.3.64); `subtotal`/`item_count` vẫn tính cả giỏ.
            'selected_subtotal' => $this->money->toArray($cart->selectedSubtotal()),
            'selected_count' => array_sum(array_map(fn (CartLineView $line): int => $line->quantity, $cart->selectedLines())),
            'checkout_ready' => $cart->isCheckoutReady(),
            'lines' => array_map(fn (CartLineView $line): array => [
                'id' => $line->id,
                'variant_id' => $line->variantId,
                'quantity' => $line->quantity,
                'selected' => $line->selected,
                'sku' => $line->variant?->sku,
                'product' => $line->variant === null ? null : [
                    'slug' => $line->variant->slug,
                    'name' => $line->variant->name,
                    'color_code' => $line->variant->colorCode,
                    'color_name' => $line->variant->colorName,
                    'size_code' => $line->variant->sizeCode,
                    'image_url' => $line->variant->imageUrl,
                ],
                'unit_price' => $this->optional($line->unitPrice),
                'compare_at' => $this->optional($line->compareAt),
                'price_when_added' => $this->optional($line->snapshotPrice),
                'line_total' => $this->optional($line->lineTotal),
                'issues' => $line->issues,
                'options' => $line->options,
            ], $cart->lines),
        ]);
    }

    /**
     * Giỏ mini cho header (§3.8): tổng số lượng, 3 dòng thêm gần nhất, tạm tính cả giỏ — không chạy khuyến mãi.
     *
     * @return array{count: int, subtotal: array<string, mixed>, lines: list<array<string, mixed>>}
     */
    public function mini(CartView $cart): array
    {
        $recent = array_slice(array_reverse($cart->lines), 0, 3);

        return [
            'count' => $cart->itemCount,
            'subtotal' => $this->money->toArray($cart->subtotal),
            'lines' => array_map(fn (CartLineView $line): array => [
                'id' => $line->id,
                'name' => $line->variant?->name,
                'variant' => $line->variant === null ? null : trim(implode(' / ', array_filter([$line->variant->colorName, $line->variant->sizeCode]))),
                'image_url' => $line->variant?->imageUrl,
                'quantity' => $line->quantity,
                'line_total' => $this->optional($line->lineTotal),
            ], $recent),
        ];
    }

    /**
     * Thay đổi giỏ cho sự kiện trình duyệt `vani:cart-updated` (đo quảng cáo, 0.3.43) — form POST (flash) và JSON dùng chung.
     *
     * @param  'add'|'remove'  $action
     * @return array{action: string, variant_id: int, sku: string|null, name: string|null, quantity: int, unit_amount: int|null, currency: string|null}
     */
    public function change(string $action, CartLineView $line, int $quantity): array
    {
        return [
            'action' => $action, 'variant_id' => $line->variantId, 'sku' => $line->variant?->sku, 'name' => $line->variant?->name,
            'quantity' => $quantity, 'unit_amount' => $line->unitPrice?->amount, 'currency' => $line->unitPrice?->currency->code,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function optional(?Money $money): ?array
    {
        return $money === null ? null : $this->money->toArray($money);
    }
}
