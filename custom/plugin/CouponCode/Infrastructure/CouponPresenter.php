<?php

declare(strict_types=1);

namespace Plugin\CouponCode\Infrastructure;

use Illuminate\Support\Carbon;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Catalog\Contracts\CategoryDirectory;
use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Checkout\Contracts\Data\VoucherPreviewResult;
use Modules\Checkout\Contracts\VoucherPreview;
use Modules\Promotion\Contracts\Data\ManagedPromotionUsage;
use Modules\Promotion\Contracts\Data\VoucherData;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Contracts\Vouchers;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreClock;
use Plugin\CouponCode\CouponCodeServiceProvider;
use Plugin\CouponCode\Domain\CouponTerms;
use Plugin\CouponCode\Persistence\Coupon;

/**
 * Dữ liệu storefront của mã giảm giá (Blade và Storefront API dùng chung): ví voucher trên giỏ, mã cho một sản phẩm,
 * mã riêng của khách. Ưu đãi/điều kiện viết sẵn thành câu cho khách.
 *
 * @phpstan-type Offer array{code: string, title: string, description: string, conditions: list<string>, expires_at: ?string, remaining: ?int, personal: bool}
 */
final class CouponPresenter
{
    /** Số mã tối đa trong ví (mỗi mã một lượt tính tổng giỏ). */
    public const WALLET_LIMIT = 20;

    public function __construct(
        private readonly MoneyFormatter $money,
        private readonly ManagedPromotions $promotions,
        private readonly Vouchers $vouchers,
        private readonly VoucherPreview $preview,
        private readonly CategoryDirectory $categories,
        private readonly CollectionDirectory $collections,
    ) {}

    /**
     * Ví voucher: mã công khai đang chạy + mã riêng còn dùng được của khách, mỗi mã tính thử trên giỏ. Mã dùng được và giảm
     * nhiều nhất xếp đầu (`best`); mã đang gắn với giỏ có `applied`.
     *
     * @return array{items: list<array<string, mixed>>, best: ?string}
     */
    public function wallet(CartKey $cart, ?int $customerId, string $locale, int $now): array
    {
        $offers = [...$this->publicOffers($locale, $now), ...($customerId === null ? [] : $this->personalOffers($customerId, $locale, $now, usableOnly: true))];
        $offers = array_slice($offers, 0, self::WALLET_LIMIT);
        if ($offers === []) {
            return ['items' => [], 'best' => null];
        }

        $results = [];
        foreach ($this->preview->forCart($cart, array_column($offers, 'code')) as $result) {
            $results[$result->code] = $result;
        }

        $items = [];
        foreach ($offers as $offer) {
            $result = $results[$offer['code']] ?? null;
            if ($result === null) {
                continue;
            }
            $items[] = $offer + [
                'applicable' => $result->applicable(),
                'applied' => $result->attached,
                'discount' => $this->money->toArray($result->discount),
                'message' => $this->message($result),
            ];
        }
        usort($items, fn (array $a, array $b): int => [$b['applicable'], $b['discount']['amount']] <=> [$a['applicable'], $a['discount']['amount']]);

        return ['items' => $items, 'best' => ($items[0]['applicable'] ?? false) ? $items[0]['code'] : null];
    }

    /**
     * Mã công khai đang chạy có phạm vi chứa sản phẩm (trang sản phẩm — giống nhau với mọi khách, cache được).
     *
     * @param  array<string, mixed>  $product  ProductViews::detail
     * @return list<Offer>
     */
    public function forProduct(array $product, string $locale, int $now): array
    {
        $productId = (int) ($product['id'] ?? 0);
        $brandSlug = isset($product['brand']['slug']) ? (string) $product['brand']['slug'] : null;
        $coupons = $this->runningPublic($now);
        if ($productId === 0 || $coupons === []) {
            return [];
        }
        $categoryIds = $this->categories->idsForProducts([$productId])[$productId] ?? [];
        $collectionSlugs = $this->collections->slugsForProducts([$productId])[$productId] ?? [];
        $usage = $this->promotions->usage(CouponCodeServiceProvider::ID);

        return array_values(array_map(fn (Coupon $coupon): array => $this->offer($coupon, (string) $coupon->code, $locale, $usage, null),
            array_filter($coupons, fn (Coupon $coupon): bool => CouponTerms::coversProduct($coupon, $productId, $brandSlug, $categoryIds, $collectionSlugs))));
    }

    /**
     * Mã riêng của khách: còn dùng được, đã dùng, hết hạn.
     *
     * @return array{usable: list<Offer>, used: list<Offer>, expired: list<Offer>}
     */
    public function mine(int $customerId, string $locale, int $now): array
    {
        $groups = ['usable' => [], 'used' => [], 'expired' => []];
        foreach ($this->personal($customerId) as [$voucher, $coupon]) {
            $offer = $this->offer($coupon, $voucher->code, $locale, [], $voucher->expiresAt, personal: true);
            $groups[$this->state($voucher, $coupon, $now)][] = $offer;
        }

        return $groups;
    }

    /**
     * @return list<Offer>
     */
    private function publicOffers(string $locale, int $now): array
    {
        $usage = $this->promotions->usage(CouponCodeServiceProvider::ID);

        return array_map(fn (Coupon $coupon): array => $this->offer($coupon, (string) $coupon->code, $locale, $usage, null), $this->runningPublic($now));
    }

    /**
     * @return list<Offer>
     */
    private function personalOffers(int $customerId, string $locale, int $now, bool $usableOnly): array
    {
        $offers = [];
        foreach ($this->personal($customerId) as [$voucher, $coupon]) {
            if (! $usableOnly || $this->state($voucher, $coupon, $now) === 'usable') {
                $offers[] = $this->offer($coupon, $voucher->code, $locale, [], $voucher->expiresAt, personal: true);
            }
        }

        return $offers;
    }

    /**
     * @return list<Coupon>
     */
    private function runningPublic(int $now): array
    {
        return Coupon::query()->where('public', true)->where('active', true)->where('kind', Coupon::KIND_SHARED)->whereNotNull('code')
            ->orderByRaw('ends_at IS NULL')->orderBy('ends_at')->orderByDesc('id')->get()
            ->filter(fn (Coupon $coupon): bool => $coupon->isRunningAt($now))->values()->all();
    }

    /**
     * @return list<array{0: VoucherData, 1: Coupon}>
     */
    private function personal(int $customerId): array
    {
        $vouchers = $this->vouchers->forCustomer($customerId);
        $coupons = Coupon::query()->whereIn('promotion_id', array_unique(array_map(fn (VoucherData $voucher): int => $voucher->promotionId, $vouchers)))
            ->get()->keyBy('promotion_id');
        $pairs = [];
        foreach ($vouchers as $voucher) {
            $coupon = $coupons->get($voucher->promotionId);
            if ($coupon instanceof Coupon) {
                $pairs[] = [$voucher, $coupon];
            }
        }

        return $pairs;
    }

    /** usable | used | expired */
    private function state(VoucherData $voucher, Coupon $coupon, int $now): string
    {
        return match (true) {
            $voucher->usageLimit !== null && $voucher->usedCount >= $voucher->usageLimit => 'used',
            ! $voucher->active, ! $coupon->active,
            $voucher->expiresAt !== null && $voucher->expiresAt <= $now,
            $coupon->ends_at !== null && $coupon->ends_at->getTimestamp() <= $now => 'expired',
            default => 'usable',
        };
    }

    /**
     * @param  array<int|string, ManagedPromotionUsage>  $usage
     * @return Offer
     */
    private function offer(Coupon $coupon, string $code, string $locale, array $usage, ?int $expiresAt, bool $personal = false): array
    {
        $used = $usage[$coupon->id] ?? null;
        $endsAt = $expiresAt ?? $coupon->ends_at?->getTimestamp();

        return [
            'code' => $code,
            'title' => $this->title($coupon),
            'description' => $coupon->description($locale),
            'conditions' => $this->conditions($coupon),
            'expires_at' => $endsAt === null ? null : StoreClock::format(Carbon::createFromTimestamp($endsAt)),
            'remaining' => $coupon->usage_limit === null || $personal ? null : max(0, $coupon->usage_limit - ($used->usageCount ?? 0)),
            'personal' => $personal,
        ];
    }

    private static function currency(): string
    {
        return (string) config('vanishop.currency');
    }

    private function title(Coupon $coupon): string
    {
        return $coupon->discount_type === 'percent'
            ? __('vani-coupon-code::texts.offer.percent', ['percent' => $coupon->discount_value])
            : __('vani-coupon-code::texts.offer.amount', ['amount' => $this->money->formatAmount($coupon->discount_value, self::currency())]);
    }

    /**
     * @return list<string>
     */
    private function conditions(Coupon $coupon): array
    {
        $conditions = [];
        if ($coupon->max_discount !== null) {
            $conditions[] = __('vani-coupon-code::texts.offer.max_discount', ['amount' => $this->money->formatAmount($coupon->max_discount, self::currency())]);
        }
        if ($coupon->min_subtotal !== null) {
            $conditions[] = __('vani-coupon-code::texts.offer.min_subtotal', ['amount' => $this->money->formatAmount($coupon->min_subtotal, self::currency())]);
        }
        if ($coupon->scope_type !== 'all') {
            $conditions[] = __("vani-coupon-code::texts.offer.scope_{$coupon->scope_type}");
        }
        if ($coupon->exclude_on_sale) {
            $conditions[] = __('vani-coupon-code::texts.offer.exclude_on_sale');
        }
        if ($coupon->customer_groups !== []) {
            $conditions[] = __('vani-coupon-code::texts.offer.customer_groups');
        }
        if ($coupon->usage_per_customer !== null) {
            $conditions[] = __('vani-coupon-code::texts.offer.per_customer', ['count' => $coupon->usage_per_customer]);
        }

        return $conditions;
    }

    private function message(VoucherPreviewResult $result): ?string
    {
        if ($result->rejection === null) {
            return $result->discount->isPositive() ? null : __('vani-coupon-code::texts.wallet.not_applicable');
        }

        return $result->rejection->hint ?? __('vani-coupon-code::texts.wallet.reason.'.$result->rejection->reason);
    }
}
