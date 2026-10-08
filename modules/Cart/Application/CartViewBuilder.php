<?php

declare(strict_types=1);

namespace Modules\Cart\Application;

use Modules\Cart\Contracts\Data\CartLineView;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Cart\Persistence\Models\Cart;
use Modules\Cart\Persistence\Models\CartLine;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Shared\Domain\Money\Money;

/**
 * Ghép dòng giỏ với catalog, giá hiện tại và số có thể bán của kênh.
 */
final class CartViewBuilder
{
    public function __construct(
        private readonly CatalogReader $catalog,
        private readonly PriceResolver $prices,
        private readonly AvailabilityReader $availability,
    ) {}

    public function build(Cart $cart, string $locale, int $now): CartView
    {
        /** @var list<CartLine> $lines */
        $lines = $cart->lines()->get()->all();
        $variantIds = array_values(array_unique(array_map(fn (CartLine $line): int => $line->variant_id, $lines)));
        // Cùng variant có thể nằm ở nhiều dòng (khác tuỳ chọn): đủ hàng xét theo tổng số lượng của variant.
        $variantQuantities = [];
        foreach ($lines as $line) {
            $variantQuantities[$line->variant_id] = ($variantQuantities[$line->variant_id] ?? 0) + $line->quantity;
        }

        $sellable = $variantIds === [] ? [] : $this->catalog->sellableVariants($variantIds, $locale, $now);
        // Giỏ của khách đăng nhập → giá theo nhóm khách (giá thành viên); giỏ vãng lai → giá chung.
        $prices = $variantIds === [] ? [] : $this->prices->forVariants($variantIds, new PricingContext($now, customerId: $cart->customer_id));
        $stock = $variantIds === [] ? [] : $this->availability->forVariants($variantIds);

        $subtotal = Money::zero($cart->currency_code);
        $itemCount = 0;
        $views = [];

        foreach ($lines as $line) {
            $variant = $sellable[$line->variant_id] ?? null;
            $price = $prices[$line->variant_id] ?? null;
            $snapshot = $line->unit_price_snapshot === null ? null : Money::of($line->unit_price_snapshot, $cart->currency_code);
            $issues = [];

            if ($variant === null || $price === null) {
                $issues[] = CartLineView::ISSUE_UNAVAILABLE;
            } elseif (($stock[$line->variant_id] ?? 0) < $variantQuantities[$line->variant_id]) {
                $issues[] = CartLineView::ISSUE_INSUFFICIENT_STOCK;
            }
            if ($price !== null && $snapshot !== null && ! $price->amount->equals($snapshot)) {
                $issues[] = CartLineView::ISSUE_PRICE_CHANGED;
            }

            $lineTotal = $price?->amount->multiply($line->quantity);
            if ($lineTotal !== null && $variant !== null) {
                $subtotal = $subtotal->add($lineTotal);
                $itemCount += $line->quantity;
            }

            $views[] = new CartLineView(
                id: $line->id,
                variantId: $line->variant_id,
                quantity: $line->quantity,
                variant: $variant,
                unitPrice: $price?->amount,
                compareAt: $price?->compareAt,
                snapshotPrice: $snapshot,
                lineTotal: $lineTotal,
                issues: $issues,
                options: (array) ($line->meta['options'] ?? []),
                priceListCode: $price?->priceListCode,
            );
        }

        return new CartView($cart->public_id, $cart->status->value, $cart->currency_code, $views, $subtotal, $itemCount);
    }
}
