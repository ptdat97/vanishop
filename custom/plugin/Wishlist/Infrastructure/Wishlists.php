<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Infrastructure;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Modules\Storefront\Contracts\CustomerProductPrices;
use Modules\Storefront\Contracts\ProductCards;
use Plugin\Wishlist\Persistence\WishlistItem;

/**
 * Danh sách yêu thích theo khách (đặc tả §2): đơn vị là mẫu (style), tối đa MAX mẫu, chỉ thêm mẫu đang bán.
 */
final class Wishlists
{
    public const MAX = 100;

    public function __construct(private readonly ProductCards $cards, private readonly CustomerProductPrices $customerPrices) {}

    /**
     * @return list<int> id mẫu, mới thêm trước
     */
    public function ids(int $customerId): array
    {
        return WishlistItem::query()->where('customer_id', $customerId)->orderByDesc('id')->pluck('style_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function remove(int $customerId, int $styleId): void
    {
        WishlistItem::query()->where('customer_id', $customerId)->where('style_id', $styleId)->delete();
    }

    /**
     * Thêm nhiều mẫu (gộp danh sách của trình duyệt): bỏ trùng, bỏ mẫu không bán, giữ giới hạn.
     *
     * @param  list<int>  $styleIds
     * @return int số mẫu đã thêm
     */
    public function add(int $customerId, array $styleIds): int
    {
        $existing = $this->ids($customerId);
        $room = self::MAX - count($existing);
        $candidates = array_slice(array_values(array_diff(array_unique(array_map('intval', $styleIds)), $existing)), 0, max(0, $room));
        if ($candidates === []) {
            return 0;
        }

        $cards = $this->cardsFor($candidates);
        $snapshot = $this->pricesFor($customerId, array_keys($cards));
        $added = 0;
        foreach (array_reverse($candidates) as $styleId) {
            $card = $cards[$styleId] ?? null;
            if ($card === null) {
                continue;
            }
            WishlistItem::query()->create([
                'customer_id' => $customerId, 'style_id' => $styleId,
                'price_amount' => $snapshot['prices'][$styleId]['amount'] ?? null, 'price_group_id' => $snapshot['group_id'],
                'in_stock' => (bool) ($card['in_stock'] ?? true),
            ]);
            $added++;
        }

        return $added;
    }

    /**
     * Thẻ sản phẩm của danh sách (mới thêm trước); mẫu không còn bán → `null` để trang hiện "Không còn bán".
     *
     * @return list<array{style_id: int, card: array<string, mixed>|null}>
     */
    public function cards(int $customerId): array
    {
        $ids = $this->ids($customerId);
        $cards = $this->cardsFor($ids);

        return array_map(fn (int $id): array => ['style_id' => $id, 'card' => $cards[$id] ?? null], $ids);
    }

    /**
     * Thẻ sản phẩm đang hiển thị theo id, chia lô theo giới hạn của ProductCards.
     *
     * @param  list<int>  $styleIds
     * @return array<int, array<string, mixed>>
     */
    public function cardsFor(array $styleIds, ?string $locale = null): array
    {
        $cards = [];
        foreach (array_chunk($styleIds, ProductCards::MAX_IDS) as $chunk) {
            foreach ($this->cards->forIds($chunk, $locale ?? App::getLocale()) as $card) {
                $cards[(int) $card['id']] = $card;
            }
        }

        return $cards;
    }

    /**
     * @param  list<int>  $styleIds
     * @return array{group_id: ?int, prices: array<int, array{amount: int, formatted: string}>}
     */
    public function pricesFor(int $customerId, array $styleIds): array
    {
        $snapshot = ['group_id' => null, 'prices' => []];
        foreach (array_chunk($styleIds, ProductCards::MAX_IDS) as $chunk) {
            $batch = $this->customerPrices->forCustomer($chunk, $customerId, App::getLocale());
            $snapshot['group_id'] = $batch['group_id'];
            $snapshot['prices'] += $batch['prices'];
        }

        return $snapshot;
    }

    /** Gộp hồ sơ khách (CustomerMerged): chuyển sang hồ sơ đích, bỏ trùng. */
    public function move(int $fromCustomerId, int $toCustomerId): void
    {
        DB::transaction(function () use ($fromCustomerId, $toCustomerId): void {
            $taken = $this->ids($toCustomerId);
            WishlistItem::query()->where('customer_id', $fromCustomerId)->whereIn('style_id', $taken)->delete();
            WishlistItem::query()->where('customer_id', $fromCustomerId)->update(['customer_id' => $toCustomerId, 'price_amount' => null, 'price_group_id' => null]);
        });
    }

    public function forget(int $customerId): void
    {
        WishlistItem::query()->where('customer_id', $customerId)->delete();
    }
}
