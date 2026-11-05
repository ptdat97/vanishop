<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Infrastructure;

use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Customer\Contracts\Customers;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Notifier;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Shared\Support\StoreLocale;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Wishlist\Persistence\WishlistItem;
use Plugin\Wishlist\WishlistServiceProvider;

/**
 * Báo có hàng lại / giảm giá cho mẫu trong danh sách yêu thích (đặc tả §2.7). Chạy khi tồn hoặc giá của variant đổi;
 * luôn cập nhật trạng thái đã thấy, chỉ gửi khi bật cấu hình. Tin loại marketing — Core chỉ gửi khi khách có consent.
 * Mỗi (khách, mẫu, loại) tối đa một tin trong COOLDOWN_DAYS ngày.
 */
final class WishlistAlerts
{
    public const BACK_IN_STOCK = 'wishlist_back_in_stock';

    public const PRICE_DROP = 'wishlist_price_drop';

    private const COOLDOWN_DAYS = 7;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly Wishlists $wishlists,
        private readonly Notifier $notifier,
        private readonly Customers $customers,
        private readonly Settings $settings,
    ) {}

    /**
     * @param  list<int>  $variantIds
     */
    public function variantsChanged(array $variantIds): void
    {
        $styleIds = array_values(array_unique(array_map(fn ($variant): int => $variant->styleId, $this->variants->find($variantIds))));
        $items = $styleIds === [] ? collect() : WishlistItem::query()->whereIn('style_id', $styleIds)->get();
        if ($items->isEmpty()) {
            return;
        }

        $cards = $this->wishlists->cardsFor($styleIds, StoreLocale::default());
        $notifyStock = (bool) $this->settings->get(WishlistServiceProvider::ID, 'notify_back_in_stock', false);
        $notifyPrice = (bool) $this->settings->get(WishlistServiceProvider::ID, 'notify_price_drop', false);

        foreach ($items as $item) {
            $card = $cards[$item->style_id] ?? null;
            if ($card === null) {
                continue;
            }
            $inStock = (bool) ($card['in_stock'] ?? false);
            $price = $card['price']['min']['amount'] ?? null;

            if ($inStock && ! $item->in_stock && $notifyStock && $this->cooledDown($item->notified_stock_at)) {
                $this->send(self::BACK_IN_STOCK, $item, $card);
                $item->notified_stock_at = now();
            }
            if ($price !== null && $item->price_amount !== null && $price < $item->price_amount && $inStock && $notifyPrice && $this->cooledDown($item->notified_price_at)) {
                $this->send(self::PRICE_DROP, $item, $card);
                $item->notified_price_at = now();
                $item->price_amount = (int) $price;
            }
            $item->in_stock = $inStock;
            $item->price_amount ??= $price === null ? null : (int) $price;
            $item->save();
        }
    }

    private function cooledDown(mixed $at): bool
    {
        return $at === null || $at->lt(now()->subDays(self::COOLDOWN_DAYS));
    }

    /**
     * @param  array<string, mixed>  $card
     */
    private function send(string $type, WishlistItem $item, array $card): void
    {
        $customer = $this->customers->find($item->customer_id);
        if ($customer === null) {
            return;
        }
        $locale = $customer->preferredLocale ?? StoreLocale::default();

        $this->notifier->notify(new NotificationRequest(
            type: $type,
            key: "{$type}:{$item->customer_id}:{$item->style_id}:".now()->format('Ymd'),
            recipient: new Recipient(phone: $customer->phone, email: $customer->email, name: $customer->fullName, customerId: $customer->id, locale: $locale),
            variables: [
                'customer_name' => $customer->fullName,
                'product_name' => (string) ($card['name'] ?? ''),
                'product_url' => LocalizedUrls::route('storefront.product', (string) $card['slug'], $locale),
                'price' => $card['price']['min']['formatted'] ?? null,
            ],
            category: NotificationRequest::MARKETING,
        ));
    }
}
