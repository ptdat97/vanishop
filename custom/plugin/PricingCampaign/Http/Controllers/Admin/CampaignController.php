<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Http\Controllers\Admin;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Pricing\Contracts\Data\PriceQuotaRule;
use Modules\Pricing\Contracts\ListPriceReader;
use Modules\Pricing\Contracts\PriceQuotas;
use Modules\Shared\Domain\Schedule\WeeklySchedule;
use Modules\Shared\Support\StoreClock;
use Modules\Shared\Support\StoreLocale;
use Plugin\PricingCampaign\Application\PricingCampaigns;
use Plugin\PricingCampaign\Persistence\PricingCampaign;
use Plugin\PricingCampaign\Persistence\PricingCampaignItem;

/**
 * Admin → Marketing → Chiến dịch giá. Mỗi chiến dịch đồng bộ thành bảng giá + campaign của Core (chỉ xem ở đó).
 */
final class CampaignController
{
    private const MAX_ITEMS = 500;

    public function __construct(
        private readonly PricingCampaigns $campaigns,
        private readonly CatalogLookup $catalog,
        private readonly CustomerSegments $segments,
    ) {}

    public function index(PriceQuotas $quotas): Response
    {
        Gate::authorize('pricing_campaign.view');
        $now = now()->getTimestamp();

        return Inertia::render('PricingCampaign::Index', [
            'campaigns' => PricingCampaign::query()->withCount('items')->orderByDesc('id')->get()->map(function (PricingCampaign $campaign) use ($now, $quotas): array {
                $list = $campaign->price_list_id === null ? null : collect($quotas->status($campaign->price_list_id))->firstWhere('scope', PriceQuotaRule::SCOPE_LIST);

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'template' => $campaign->template,
                    'state' => $campaign->state($now),
                    'period' => [StoreClock::format($campaign->starts_at), StoreClock::format($campaign->ends_at)],
                    'recurring' => $campaign->recurrence !== null,
                    'items_count' => $campaign->items_count,
                    'sold' => $list?->sold,
                    'quota' => $list?->limit,
                    'edit_url' => route('admin.plugins.vani-pricing-campaign.edit', $campaign->id),
                ];
            })->all(),
            'urls' => ['create' => route('admin.plugins.vani-pricing-campaign.create'), 'storefront' => vani_route('storefront.p.vani-pricing-campaign.index')],
            'can' => ['manage' => Gate::allows('pricing_campaign.manage')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('pricing_campaign.manage');

        return $this->form(null);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('pricing_campaign.manage');
        $campaign = $this->campaigns->save($this->validated($request));

        return redirect()->route('admin.plugins.vani-pricing-campaign.edit', $campaign->id)->with('success', __('vani-pricing-campaign::texts.saved'));
    }

    public function edit(PricingCampaign $campaign): Response
    {
        Gate::authorize('pricing_campaign.view');

        return $this->form($campaign->load('items'));
    }

    public function update(Request $request, PricingCampaign $campaign): RedirectResponse
    {
        Gate::authorize('pricing_campaign.manage');
        $this->campaigns->save($this->validated($request), $campaign, (int) $request->input('lock_version'));

        return back()->with('success', __('vani-pricing-campaign::texts.saved'));
    }

    public function destroy(PricingCampaign $campaign): RedirectResponse
    {
        Gate::authorize('pricing_campaign.manage');
        $this->campaigns->delete($campaign);

        return redirect()->route('admin.plugins.vani-pricing-campaign.index')->with('success', __('vani-pricing-campaign::texts.deleted'));
    }

    /** Tìm sản phẩm theo tên/mã, hoặc mọi sản phẩm của một danh mục (kể cả danh mục con). */
    public function products(Request $request, CatalogReader $reader, ListPriceReader $listPrices, VariantDirectory $variants): JsonResponse
    {
        Gate::authorize('pricing_campaign.manage');
        $data = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'category_id' => ['nullable', 'integer', 'min:1']]);
        $locale = StoreLocale::default();
        if (isset($data['category_id'])) {
            $category = $this->catalog->visible('category', [(int) $data['category_id']], $locale)[(int) $data['category_id']] ?? null;
            $ids = $category === null ? [] : $reader->productIds(new ProductFilters(now()->getTimestamp(), categorySlug: $category['slug'], perPage: self::MAX_ITEMS), $locale);
            $rows = array_values($this->catalog->visible('product', $ids, $locale));
        } else {
            $rows = $this->catalog->search('product', (string) ($data['q'] ?? ''), $locale);
        }
        $ranges = $this->priceRanges(array_map(fn (array $row): int => (int) $row['id'], $rows), $listPrices, $variants);

        return response()->json(['data' => array_map(fn (array $row): array => [
            'value' => (string) $row['id'],
            'label' => $row['name'].(($row['visible'] ?? true) ? '' : __('vani-pricing-campaign::texts.hidden')),
            'list_min' => $ranges[(int) $row['id']][0] ?? null,
            'list_max' => $ranges[(int) $row['id']][1] ?? null,
        ], $rows)]);
    }

    public function categories(Request $request): JsonResponse
    {
        Gate::authorize('pricing_campaign.manage');
        $data = $request->validate(['q' => ['nullable', 'string', 'max:120']]);

        return response()->json(['data' => array_map(fn (array $row): array => ['value' => (string) $row['id'], 'label' => $row['name']],
            $this->catalog->search('category', (string) ($data['q'] ?? ''), StoreLocale::default()))]);
    }

    private function form(?PricingCampaign $campaign): Response
    {
        $listPrices = app(ListPriceReader::class);
        $variants = app(VariantDirectory::class);
        $locale = StoreLocale::default();
        $productIds = $campaign?->items->pluck('product_id')->all() ?? [];
        $names = $this->catalog->visible('product', $productIds, $locale);
        $ranges = $this->priceRanges($productIds, $listPrices, $variants);

        return Inertia::render('PricingCampaign::Form', [
            'campaign' => $campaign === null ? null : [
                'id' => $campaign->id,
                'template' => $campaign->template,
                'name' => $campaign->name,
                'label' => $campaign->text('label', $locale),
                'badge' => $campaign->text('badge', $locale),
                'content' => $campaign->text('content', $locale),
                'badge_style' => $campaign->badge_style,
                'accent_color' => $campaign->accent_color,
                'banner_media_id' => $campaign->banner_media_id,
                'show_countdown' => $campaign->show_countdown,
                'show_stock' => $campaign->show_stock,
                'priority' => $campaign->priority,
                'customer_group_id' => $campaign->customer_group_id,
                'starts_at' => StoreClock::toInput($campaign->starts_at),
                'ends_at' => StoreClock::toInput($campaign->ends_at),
                'recurrence' => $campaign->recurrence,
                'quota_total' => $campaign->quota_total,
                'per_customer_total' => $campaign->per_customer_total,
                'status' => $campaign->status,
                'state' => $campaign->state(now()->getTimestamp()),
                'lock_version' => $campaign->lock_version,
                'items' => $campaign->items->map(fn (PricingCampaignItem $item): array => [
                    'product_id' => $item->product_id,
                    'label' => $names[$item->product_id]['name'] ?? '#'.$item->product_id.__('vani-pricing-campaign::texts.hidden'),
                    'strategy' => $item->strategy, 'value' => $item->value, 'quota' => $item->quota, 'per_customer' => $item->per_customer,
                    'list_min' => $ranges[$item->product_id][0] ?? null, 'list_max' => $ranges[$item->product_id][1] ?? null,
                ])->all(),
                'warnings' => $this->warnings($campaign),
            ],
            'groups' => array_map(fn (array $group): array => ['value' => $group['id'], 'label' => $group['name']], $this->segments->groups()),
            'options' => [
                'templates' => PricingCampaign::TEMPLATES, 'badge_styles' => PricingCampaign::BADGE_STYLES,
                'statuses' => PricingCampaign::STATUSES, 'strategies' => PricingCampaignItem::STRATEGIES, 'max_items' => self::MAX_ITEMS,
            ],
            'urls' => [
                'index' => route('admin.plugins.vani-pricing-campaign.index'),
                'save' => $campaign === null ? route('admin.plugins.vani-pricing-campaign.store') : route('admin.plugins.vani-pricing-campaign.update', $campaign->id),
                'delete' => $campaign === null ? null : route('admin.plugins.vani-pricing-campaign.destroy', $campaign->id),
                'products' => route('admin.plugins.vani-pricing-campaign.products'),
                'categories' => route('admin.plugins.vani-pricing-campaign.categories'),
            ],
            'can' => ['manage' => Gate::allows('pricing_campaign.manage')],
        ]);
    }

    /**
     * Cảnh báo không chặn lưu: suất lớn hơn tồn bán được; sản phẩm đang ở chiến dịch khác đang bật cùng lúc.
     *
     * @return list<string>
     */
    private function warnings(PricingCampaign $campaign): array
    {
        $warnings = [];
        $byProduct = app(VariantDirectory::class)->ofProducts($campaign->items->pluck('product_id')->all());
        $ats = app(AvailabilityReader::class)->forVariants(array_map(fn (VariantData $variant): int => $variant->id, array_merge(...array_values($byProduct ?: [[]]))));
        $names = $this->catalog->visible('product', $campaign->items->pluck('product_id')->all(), StoreLocale::default());
        foreach ($campaign->items as $item) {
            $stock = array_sum(array_map(fn (VariantData $variant): int => max(0, (int) ($ats[$variant->id] ?? 0)), $byProduct[$item->product_id] ?? []));
            if ($item->quota !== null && $item->quota > $stock) {
                $warnings[] = __('vani-pricing-campaign::texts.warning_quota_over_stock', ['name' => $names[$item->product_id]['name'] ?? '#'.$item->product_id, 'quota' => $item->quota, 'stock' => $stock]);
            }
        }
        $overlap = PricingCampaign::query()->whereKeyNot($campaign->id)->where('status', 'active')
            ->where('starts_at', '<', $campaign->ends_at)->where('ends_at', '>', $campaign->starts_at)
            ->whereHas('items', fn ($query) => $query->whereIn('product_id', $campaign->items->pluck('product_id')))->pluck('name')->all();
        if ($overlap !== []) {
            $warnings[] = __('vani-pricing-campaign::texts.warning_overlap', ['names' => implode(', ', $overlap)]);
        }

        return $warnings;
    }

    /**
     * @param  list<int>  $productIds
     * @return array<int, array{0: int, 1: int}> giá niêm yết thấp nhất – cao nhất theo sản phẩm
     */
    private function priceRanges(array $productIds, ListPriceReader $listPrices, VariantDirectory $variants): array
    {
        $byProduct = $variants->ofProducts($productIds);
        $prices = $listPrices->forVariants(array_map(fn (VariantData $variant): int => $variant->id, array_merge(...array_values($byProduct ?: [[]]))));
        $ranges = [];
        foreach ($byProduct as $productId => $list) {
            $amounts = array_values(array_filter(array_map(fn (VariantData $variant): ?int => $prices[$variant->id] ?? null, $list), fn (?int $amount): bool => $amount !== null));
            if ($amounts !== []) {
                $ranges[$productId] = [min($amounts), max($amounts)];
            }
        }

        return $ranges;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $percent = fn (int $index): bool => $request->input("items.{$index}.strategy") === 'percent';
        $data = $request->validate([
            'template' => ['required', Rule::in(PricingCampaign::TEMPLATES)],
            'name' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:120'],
            'badge' => ['nullable', 'string', 'max:40'],
            'content' => ['nullable', 'string', 'max:1000'],
            'badge_style' => ['required', Rule::in(PricingCampaign::BADGE_STYLES)],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'banner_media_id' => ['nullable', 'integer', 'min:1'],
            'show_countdown' => ['required', 'boolean'],
            'show_stock' => ['required', 'boolean'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'customer_group_id' => ['nullable', 'integer', Rule::in(array_column($this->segments->groups(), 'id'))],
            'starts_at' => ['required', 'date_format:'.StoreClock::INPUT_FORMAT],
            'ends_at' => ['required', 'date_format:'.StoreClock::INPUT_FORMAT],
            'recurrence' => ['nullable', 'array', fn (string $attribute, mixed $value, Closure $fail) => $value === [] || WeeklySchedule::isValid($value) ? null : $fail(__('vani-pricing-campaign::texts.recurrence_invalid'))],
            'quota_total' => ['nullable', 'integer', 'min:1'],
            'per_customer_total' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(PricingCampaign::STATUSES)],
            'items' => ['required', 'array', 'list', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*' => ['required', 'array:product_id,strategy,value,quota,per_customer'],
            'items.*.product_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.strategy' => ['required', Rule::in(PricingCampaignItem::STRATEGIES)],
            'items.*.value' => ['required', 'integer', 'min:1', fn (string $attribute, mixed $value, Closure $fail) => $percent((int) explode('.', $attribute)[1]) && (int) $value > 99 ? $fail(__('vani-pricing-campaign::texts.percent_range')) : null],
            'items.*.quota' => ['nullable', 'integer', 'min:1'],
            'items.*.per_customer' => ['nullable', 'integer', 'min:1'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ], ['items.min' => __('vani-pricing-campaign::texts.items_required'), 'items.required' => __('vani-pricing-campaign::texts.items_required')]);

        $data['starts_at'] = StoreClock::fromInput((string) $data['starts_at']);
        $data['ends_at'] = StoreClock::fromInput((string) $data['ends_at']);
        if ($data['ends_at']->lessThanOrEqualTo($data['starts_at'])) {
            throw ValidationException::withMessages(['ends_at' => __('vani-pricing-campaign::texts.ends_after_starts')]);
        }
        if (isset($data['banner_media_id'])) {
            $image = app(MediaDirectory::class)->find([(int) $data['banner_media_id']])[(int) $data['banner_media_id']] ?? null;
            if ($image === null || ! str_starts_with($image->mimeType, 'image/')) {
                throw ValidationException::withMessages(['banner_media_id' => __('storefront::texts.page_blocks.choose_image')]);
            }
        }
        $known = app(VariantDirectory::class)->ofProducts(array_map(fn (array $item): int => (int) $item['product_id'], $data['items']));
        foreach ($data['items'] as $index => $item) {
            if (! isset($known[(int) $item['product_id']])) {
                throw ValidationException::withMessages(["items.{$index}.product_id" => __('vani-pricing-campaign::texts.unknown_product')]);
            }
        }

        return $data;
    }
}
