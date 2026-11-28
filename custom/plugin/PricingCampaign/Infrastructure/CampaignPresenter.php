<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Infrastructure;

use Illuminate\Support\Carbon;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Pricing\Contracts\Data\PriceQuotaRule;
use Modules\Pricing\Contracts\Data\PriceQuotaStatus;
use Modules\Pricing\Contracts\PriceQuotas;
use Modules\Shared\Support\StoreClock;
use Modules\Storefront\Contracts\ProductCards;
use Plugin\PricingCampaign\Persistence\PricingCampaign;
use Plugin\PricingCampaign\Persistence\PricingCampaignItem;

/**
 * Dữ liệu storefront của chiến dịch giá (Blade, block Builder, Storefront API dùng chung). Một instance mỗi request (scoped):
 * chiến dịch đang bật nạp một lần cho mọi thẻ sản phẩm trên trang.
 *
 * @phpstan-type Offer array{campaign_id: int, label: string, badge: string, badge_style: string, badge_class: string, accent_color: ?string, running: bool, starts_at: ?string, ends_at: string, ends_at_ts: int, starts_at_ts: ?int, show_countdown: bool, show_stock: bool, limit: ?int, sold: int, remaining: ?int, sold_percent: ?int, per_customer: ?int}
 */
final class CampaignPresenter
{
    /** Màu badge của theme mặc định theo kiểu đã chọn. */
    public const BADGE_CLASSES = ['danger' => 'bg-red-600 text-white', 'warning' => 'bg-amber-500 text-white', 'success' => 'bg-emerald-600 text-white', 'info' => 'bg-sky-600 text-white', 'dark' => 'bg-slate-900 text-white'];

    /** @var list<PricingCampaign>|null */
    private ?array $active = null;

    /** @var array<int, list<PriceQuotaStatus>> */
    private array $quotas = [];

    public function __construct(
        private readonly PriceQuotas $priceQuotas,
        private readonly ProductCards $cards,
        private readonly MediaDirectory $media,
    ) {}

    /**
     * Chiến dịch đang chạy (ưu tiên cao nhất) có sản phẩm này; không có thì chiến dịch sắp chạy gần nhất.
     *
     * @return Offer|null
     */
    public function forProduct(int $productId, string $locale, int $now): ?array
    {
        $upcoming = null;
        foreach ($this->active($now) as $campaign) {
            $item = $campaign->items->firstWhere('product_id', $productId);
            if (! $item instanceof PricingCampaignItem) {
                continue;
            }
            if ($campaign->isRunningAt($now)) {
                return $this->offer($campaign, $item, $locale, $now);
            }
            $upcoming ??= $this->offer($campaign, $item, $locale, $now);
        }

        return $upcoming;
    }

    /**
     * Trang Ưu đãi / API: chiến dịch đang chạy và sắp chạy, kèm thẻ sản phẩm.
     *
     * @return list<array<string, mixed>>
     */
    public function listing(string $locale, int $now): array
    {
        return array_map(fn (PricingCampaign $campaign): array => $this->campaign($campaign, $locale, $now), $this->active($now));
    }

    /**
     * Một chiến dịch cho block Builder: id cụ thể, hoặc 0 = chiến dịch đang chạy ưu tiên cao nhất.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id, string $locale, int $now, int $limit = 8): ?array
    {
        foreach ($this->active($now) as $campaign) {
            if (($id === 0 && $campaign->isRunningAt($now)) || $campaign->id === $id) {
                return $this->campaign($campaign, $locale, $now, $limit);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function campaign(PricingCampaign $campaign, string $locale, int $now, int $limit = ProductCards::MAX_IDS): array
    {
        $running = $campaign->isRunningAt($now);
        $start = $running ? null : $campaign->nextStartAt($now);
        $end = $running ? $campaign->currentEndAt($now) : $campaign->ends_at->getTimestamp();
        $status = $this->listQuota($campaign);

        return [
            'id' => $campaign->id,
            'label' => $campaign->text('label', $locale) ?: $campaign->name,
            'badge' => $campaign->text('badge', $locale),
            'badge_style' => $campaign->badge_style,
            'accent_color' => $campaign->accent_color,
            'content' => $campaign->text('content', $locale),
            'banner_url' => $campaign->banner_media_id === null ? null : ($this->media->find([$campaign->banner_media_id])[$campaign->banner_media_id] ?? null)?->urlFor(1600),
            'running' => $running,
            'starts_at' => $start === null ? null : StoreClock::iso(Carbon::createFromTimestamp($start)),
            'ends_at' => StoreClock::iso(Carbon::createFromTimestamp($end)),
            'show_countdown' => $campaign->show_countdown,
            'show_stock' => $campaign->show_stock,
            'limit' => $status?->limit,
            'sold' => $status === null ? 0 : $status->sold,
            'remaining' => $status?->remaining(),
            'products' => $this->cards->forIds(array_slice($campaign->items->pluck('product_id')->all(), 0, $limit), $locale),
        ];
    }

    /**
     * @return Offer
     */
    private function offer(PricingCampaign $campaign, PricingCampaignItem $item, string $locale, int $now): array
    {
        $running = $campaign->isRunningAt($now);
        $start = $running ? null : $campaign->nextStartAt($now);
        $end = $running ? $campaign->currentEndAt($now) : $campaign->ends_at->getTimestamp();
        $quota = $this->productQuota($campaign, $item) ?? $this->listQuota($campaign);
        $limit = $quota?->limit;
        $sold = $quota === null ? 0 : $quota->sold;

        return [
            'campaign_id' => $campaign->id,
            'label' => $campaign->text('label', $locale) ?: $campaign->name,
            'badge' => $campaign->text('badge', $locale) ?: ($campaign->text('label', $locale) ?: $campaign->name),
            'badge_style' => $campaign->badge_style,
            'badge_class' => self::BADGE_CLASSES[$campaign->badge_style] ?? self::BADGE_CLASSES['danger'],
            'accent_color' => $campaign->accent_color,
            'running' => $running,
            'starts_at' => $start === null ? null : StoreClock::iso(Carbon::createFromTimestamp($start)),
            'ends_at' => (string) StoreClock::iso(Carbon::createFromTimestamp($end)),
            'ends_at_ts' => $end,
            'starts_at_ts' => $start,
            'show_countdown' => $campaign->show_countdown,
            'show_stock' => $campaign->show_stock && $limit !== null,
            'limit' => $limit,
            'sold' => $sold,
            'remaining' => $quota?->remaining(),
            'sold_percent' => $limit === null || $limit === 0 ? null : min(100, intdiv($sold * 100, $limit)),
            'per_customer' => $item->per_customer ?? $campaign->per_customer_total,
        ];
    }

    private function productQuota(PricingCampaign $campaign, PricingCampaignItem $item): ?PriceQuotaStatus
    {
        foreach ($this->quotasOf($campaign) as $status) {
            if ($status->scope === PriceQuotaRule::SCOPE_PRODUCT && $status->scopeId === $item->product_id && $status->limit !== null) {
                return $status;
            }
        }

        return null;
    }

    private function listQuota(PricingCampaign $campaign): ?PriceQuotaStatus
    {
        foreach ($this->quotasOf($campaign) as $status) {
            if ($status->scope === PriceQuotaRule::SCOPE_LIST && $status->limit !== null) {
                return $status;
            }
        }

        return null;
    }

    /**
     * @return list<PriceQuotaStatus>
     */
    private function quotasOf(PricingCampaign $campaign): array
    {
        return $campaign->price_list_id === null ? [] : ($this->quotas[$campaign->price_list_id] ??= $this->priceQuotas->status($campaign->price_list_id));
    }

    /**
     * Chiến dịch đang bật, chưa kết thúc, còn lượt chạy (đang chạy hoặc sắp tới) — ưu tiên cao trước.
     *
     * @return list<PricingCampaign>
     */
    private function active(int $now): array
    {
        return $this->active ??= PricingCampaign::query()->where('status', 'active')->where('ends_at', '>', Carbon::createFromTimestamp($now))
            ->with('items')->orderByDesc('priority')->orderBy('starts_at')->get()
            ->filter(fn (PricingCampaign $campaign): bool => $campaign->isRunningAt($now) || $campaign->nextStartAt($now) !== null)
            ->values()->all();
    }
}
