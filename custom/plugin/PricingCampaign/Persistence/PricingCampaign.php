<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Shared\Domain\Schedule\WeeklySchedule;
use Modules\Shared\Support\StoreClock;
use Modules\Shared\Support\StoreLocale;

/**
 * @property int $id
 * @property string $template
 * @property string $name
 * @property array<string, array{label?: string, badge?: string, content?: string}> $translations
 * @property string $badge_style
 * @property string|null $accent_color
 * @property int|null $banner_media_id
 * @property bool $show_countdown
 * @property bool $show_stock
 * @property int $priority
 * @property int|null $customer_group_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property list<array{days: list<int>, from: string, to: string}>|null $recurrence
 * @property int|null $quota_total
 * @property int|null $per_customer_total
 * @property string $status draft | active | stopped
 * @property int|null $price_list_id
 * @property int|null $campaign_id
 * @property int $lock_version
 */
final class PricingCampaign extends Model
{
    public const TEMPLATES = ['flash_sale', 'hot_deal', 'member', 'weekend', 'custom'];

    public const BADGE_STYLES = ['danger', 'warning', 'success', 'info', 'dark'];

    public const STATUSES = ['draft', 'active', 'stopped'];

    protected $table = 'plg_pricing_campaigns';

    protected $fillable = ['template', 'name', 'translations', 'badge_style', 'accent_color', 'banner_media_id', 'show_countdown', 'show_stock', 'priority',
        'customer_group_id', 'starts_at', 'ends_at', 'recurrence', 'quota_total', 'per_customer_total', 'status', 'price_list_id', 'campaign_id', 'lock_version'];

    protected function casts(): array
    {
        return [
            'translations' => 'array', 'banner_media_id' => 'integer', 'show_countdown' => 'boolean', 'show_stock' => 'boolean', 'priority' => 'integer',
            'customer_group_id' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'recurrence' => 'array', 'quota_total' => 'integer',
            'per_customer_total' => 'integer', 'price_list_id' => 'integer', 'campaign_id' => 'integer', 'lock_version' => 'integer',
        ];
    }

    /**
     * @return HasMany<PricingCampaignItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PricingCampaignItem::class)->orderBy('position');
    }

    /** Chữ cho khách theo ngôn ngữ (nhãn, badge, nội dung); thiếu thì ngôn ngữ mặc định. */
    public function text(string $field, string $locale): string
    {
        return (string) ($this->translations[$locale][$field] ?? $this->translations[StoreLocale::default()][$field] ?? '');
    }

    public function schedule(): ?WeeklySchedule
    {
        return WeeklySchedule::fromArray($this->recurrence);
    }

    /** Đang bán giá chiến dịch: bật, trong khoảng thời gian và trong khung giờ của lịch lặp. */
    public function isRunningAt(int $now): bool
    {
        return $this->status === 'active' && $this->starts_at->getTimestamp() <= $now && $this->ends_at->getTimestamp() > $now
            && ($this->schedule()?->isOpenAt($now, StoreClock::zone()) ?? true);
    }

    /** Mốc kết thúc của lượt đang chạy (hết khung giờ hoặc hết chiến dịch) — đếm ngược. */
    public function currentEndAt(int $now): int
    {
        $end = $this->ends_at->getTimestamp();
        $close = $this->schedule()?->nextBoundaryAfter($now, StoreClock::zone());

        return $close === null ? $end : min($end, $close);
    }

    /** Mốc bắt đầu lượt kế tiếp (chiến dịch chưa tới giờ hoặc ngoài khung); null = không còn lượt nào. */
    public function nextStartAt(int $now): ?int
    {
        if ($this->status !== 'active' || $this->ends_at->getTimestamp() <= $now) {
            return null;
        }
        $from = max($now, $this->starts_at->getTimestamp());
        $schedule = $this->schedule();
        if ($schedule === null || $schedule->isOpenAt($from, StoreClock::zone())) {
            return $from;
        }
        $next = $schedule->nextBoundaryAfter($from, StoreClock::zone());

        return $next !== null && $next < $this->ends_at->getTimestamp() ? $next : null;
    }

    /** draft | scheduled | running | waiting (ngoài khung giờ) | ended | stopped */
    public function state(int $now): string
    {
        return match (true) {
            $this->status === 'stopped' => 'stopped',
            $this->status === 'draft' => 'draft',
            $this->ends_at->getTimestamp() <= $now => 'ended',
            $this->starts_at->getTimestamp() > $now => 'scheduled',
            ! $this->isRunningAt($now) => 'waiting',
            default => 'running',
        };
    }
}
