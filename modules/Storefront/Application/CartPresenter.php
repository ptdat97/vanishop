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
            'checkout_ready' => $cart->isCheckoutReady(),
            'lines' => array_map(fn (CartLineView $line): array => [
                'id' => $line->id,
                'variant_id' => $line->variantId,
                'quantity' => $line->quantity,
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
            ], $cart->lines),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function optional(?Money $money): ?array
    {
        return $money === null ? null : $this->money->toArray($money);
    }
}
