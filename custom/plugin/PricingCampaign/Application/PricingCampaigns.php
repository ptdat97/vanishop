<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Contracts\Data\ManagedPriceList;
use Modules\Pricing\Contracts\Data\PriceQuotaRule;
use Modules\Pricing\Contracts\ListPriceReader;
use Modules\Pricing\Contracts\ManagedPriceLists;
use Modules\Promotion\Contracts\Campaigns;
use Modules\Promotion\Contracts\Data\ManagedCampaign;
use Modules\Shared\Domain\Schedule\WeeklySchedule;
use Modules\Shared\Support\StoreLocale;
use Plugin\PricingCampaign\Domain\CampaignPricing;
use Plugin\PricingCampaign\Persistence\PricingCampaign;
use Plugin\PricingCampaign\PricingCampaignServiceProvider;

/**
 * Lưu chiến dịch giá và đồng bộ sang Core: giá từng variant (từ giá niêm yết) + suất → bảng giá do plugin quản lý; lịch +
 * trạng thái → campaign của Core (ghi xuống bảng giá, báo cáo dùng chung).
 */
final class PricingCampaigns
{
    public const EDIT_ROUTE = 'admin.plugins.vani-pricing-campaign.edit';

    public const MEDIA_OWNER = 'vani.pricing-campaign';

    public function __construct(
        private readonly ManagedPriceLists $priceLists,
        private readonly Campaigns $campaigns,
        private readonly ListPriceReader $listPrices,
        private readonly VariantDirectory $variants,
        private readonly MediaDirectory $media,
    ) {}

    /**
     * @param  array<string, mixed>  $data  đã validate (CampaignController)
     */
    public function save(array $data, ?PricingCampaign $campaign = null, ?int $expectedLockVersion = null): PricingCampaign
    {
        return DB::transaction(function () use ($data, $campaign, $expectedLockVersion): PricingCampaign {
            if ($campaign !== null) {
                if (PricingCampaign::query()->whereKey($campaign->id)->where('lock_version', $expectedLockVersion)->increment('lock_version') === 0) {
                    throw ValidationException::withMessages(['lock_version' => __('vani-pricing-campaign::texts.stale')]);
                }
                $campaign->refresh();
            }
            $campaign ??= new PricingCampaign;
            $translations = (array) ($campaign->translations ?? []);
            $translations[StoreLocale::default()] = ['label' => $data['label'] ?? '', 'badge' => $data['badge'] ?? '', 'content' => $data['content'] ?? ''];
            $campaign->fill([
                'template' => $data['template'], 'name' => $data['name'], 'translations' => $translations, 'badge_style' => $data['badge_style'],
                'accent_color' => $data['accent_color'] ?? null, 'banner_media_id' => $data['banner_media_id'] ?? null,
                'show_countdown' => (bool) $data['show_countdown'], 'show_stock' => (bool) $data['show_stock'], 'priority' => (int) $data['priority'],
                'customer_group_id' => $data['customer_group_id'] ?? null, 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'],
                'recurrence' => WeeklySchedule::fromArray($data['recurrence'] ?? null)?->toArray(),
                'quota_total' => $data['quota_total'] ?? null, 'per_customer_total' => $data['per_customer_total'] ?? null, 'status' => $data['status'],
            ])->save();
            $campaign->items()->delete();
            foreach (array_values($data['items']) as $position => $item) {
                $campaign->items()->create([
                    'product_id' => $item['product_id'], 'strategy' => $item['strategy'], 'value' => $item['value'],
                    'quota' => $item['quota'] ?? null, 'per_customer' => $item['per_customer'] ?? null, 'position' => $position,
                ]);
            }
            $campaign->load('items');
            $this->sync($campaign);
            $this->media->syncUsages(self::MEDIA_OWNER, $campaign->id, 'banner', $campaign->banner_media_id === null ? [] : [$campaign->banner_media_id]);

            return $campaign;
        });
    }

    public function delete(PricingCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $this->campaigns->remove(PricingCampaignServiceProvider::ID, (string) $campaign->id);
            $this->priceLists->remove(PricingCampaignServiceProvider::ID, (string) $campaign->id);
            $this->media->releaseUsages(self::MEDIA_OWNER, $campaign->id);
            $campaign->delete();
        });
    }

    /** Ghi giá + suất + lịch xuống Core; lỗi giá (vượt trần giảm…) báo tại dòng sản phẩm. */
    public function sync(PricingCampaign $campaign): void
    {
        $campaign->loadMissing('items');
        $byProduct = $this->variants->ofProducts($campaign->items->pluck('product_id')->all());
        $listPrices = $this->listPrices->forVariants(array_map(fn (VariantData $variant): int => $variant->id, array_merge(...array_values($byProduct ?: [[]]))));

        $prices = [];
        $owner = [];
        $quotas = [];
        foreach ($campaign->items as $index => $item) {
            $priced = 0;
            foreach ($byProduct[$item->product_id] ?? [] as $variant) {
                $price = isset($listPrices[$variant->id]) ? CampaignPricing::price($item->strategy, $item->value, $listPrices[$variant->id]) : null;
                if ($price !== null) {
                    $prices[$variant->id] = $price;
                    $owner[$variant->id] = $index;
                    $priced++;
                }
            }
            if ($priced === 0) {
                throw ValidationException::withMessages(["items.{$index}.value" => __('vani-pricing-campaign::texts.not_below_list_price')]);
            }
            if ($item->quota !== null || $item->per_customer !== null) {
                $quotas[] = new PriceQuotaRule(PriceQuotaRule::SCOPE_PRODUCT, $item->product_id, $item->quota, $item->per_customer);
            }
        }
        if ($campaign->quota_total !== null || $campaign->per_customer_total !== null) {
            $quotas[] = new PriceQuotaRule(PriceQuotaRule::SCOPE_LIST, 0, $campaign->quota_total, $campaign->per_customer_total);
        }

        $recurrence = $campaign->schedule();
        try {
            $listId = $this->priceLists->sync(PricingCampaignServiceProvider::ID, (string) $campaign->id, new ManagedPriceList(
                name: __('vani-pricing-campaign::texts.price_list_name', ['name' => $campaign->name]),
                type: $campaign->customer_group_id === null ? ManagedPriceList::TYPE_SALE : ManagedPriceList::TYPE_MEMBER,
                active: $campaign->status === 'active',
                prices: $prices,
                priority: $campaign->priority,
                customerGroupId: $campaign->customer_group_id,
                startsAt: $campaign->starts_at->getTimestamp(),
                endsAt: $campaign->ends_at->getTimestamp(),
                editRoute: self::EDIT_ROUTE,
                recurrence: $recurrence,
                quotas: $quotas,
            ));
        } catch (ValidationException $invalid) {
            $messages = [];
            foreach ($invalid->errors() as $key => $errors) {
                $variantId = str_starts_with($key, 'prices.') ? (int) substr($key, 7) : null;
                $messages[$variantId !== null && isset($owner[$variantId]) ? "items.{$owner[$variantId]}.value" : 'business'] = $errors;
            }
            throw ValidationException::withMessages($messages);
        }
        $campaignId = $this->campaigns->sync(PricingCampaignServiceProvider::ID, (string) $campaign->id, new ManagedCampaign(
            name: $campaign->name,
            status: $campaign->status,
            startsAt: $campaign->starts_at->getTimestamp(),
            endsAt: $campaign->ends_at->getTimestamp(),
            priceListIds: [$listId],
            recurrence: $recurrence,
            editRoute: self::EDIT_ROUTE,
        ));
        $campaign->forceFill(['price_list_id' => $listId, 'campaign_id' => $campaignId])->saveQuietly();
    }

    /**
     * Giá niêm yết đổi → tính lại giá của chiến dịch chưa kết thúc chứa các sản phẩm đó. Lỗi một chiến dịch (vd. giá mới làm vượt
     * trần) không chặn chiến dịch khác — chiến dịch giữ giá cũ, ghi log.
     *
     * @param  list<int>  $variantIds
     */
    public function listPricesChanged(array $variantIds): void
    {
        $productIds = array_values(array_unique(array_map(fn (VariantData $variant): int => $variant->productId, $this->variants->find($variantIds))));
        if ($productIds === []) {
            return;
        }
        $campaigns = PricingCampaign::query()->where('status', '!=', 'stopped')->where('ends_at', '>', now())
            ->whereHas('items', fn ($query) => $query->whereIn('product_id', $productIds))->with('items')->get();
        foreach ($campaigns as $campaign) {
            try {
                DB::transaction(fn () => $this->sync($campaign));
            } catch (ValidationException $invalid) {
                report($invalid);
            }
        }
    }
}
