<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Http\Controllers\BrandWorkspaceHome;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Channel\Contracts\ChannelDirectory;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Pricing\Application\PriceListQueries;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Http\Requests\PriceListRequest;
use Modules\Pricing\Http\Requests\PricesRequest;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;

final class PriceListController
{
    public function home(BrandWorkspaceHome $home): Response|RedirectResponse
    {
        Gate::authorize('pricing.view');

        return $home->respond('admin.pricing.price-lists.index', 'Giá bán', 'Chọn brand để quản lý bảng giá.');
    }

    public function index(Brand $brand, PriceListQueries $queries): Response
    {
        Gate::authorize('pricing.view', [ScopeRef::brand($brand->id)]);

        $lists = PriceList::query()->withCount('prices')->orderByDesc('priority')->orderBy('code')->get();
        $channelCounts = $queries->channelCounts($lists->pluck('id')->all());

        return Inertia::render('Pricing::PriceLists/Index', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'baseUrl' => route('admin.pricing.price-lists.index'),
            'priceLists' => $lists
                ->map(fn (PriceList $list): array => [
                    'id' => $list->id,
                    'code' => $list->code,
                    'name' => $list->name,
                    'type' => $list->type->value,
                    'priority' => $list->priority,
                    'status' => $list->status,
                    'starts_at' => $list->starts_at?->format('d/m/Y H:i'),
                    'ends_at' => $list->ends_at?->format('d/m/Y H:i'),
                    'prices_count' => $list->prices_count,
                    'channels_count' => (int) ($channelCounts[$list->id] ?? 0),
                ])->all(),
            'canManage' => Gate::allows('pricing.manage', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function create(Brand $brand, ChannelDirectory $channels, PriceListQueries $queries): Response
    {
        Gate::authorize('pricing.manage', [ScopeRef::brand($brand->id)]);

        return $this->form($brand, null, $channels, $queries);
    }

    public function store(Brand $brand, PriceListRequest $request, PriceListService $service): RedirectResponse
    {
        $list = $service->save($brand->id, $request->toData(), array_map('intval', (array) $request->validated('channel_ids', [])));

        return redirect()->route('admin.pricing.price-lists.edit', ['priceList' => $list->id])->with('success', __('pricing::messages.saved'));
    }

    public function edit(Brand $brand, PriceList $priceList, ChannelDirectory $channels, PriceListQueries $queries): Response
    {
        Gate::authorize('pricing.manage', [ScopeRef::brand($brand->id)]);

        return $this->form($brand, $priceList, $channels, $queries);
    }

    public function update(Brand $brand, PriceList $priceList, PriceListRequest $request, PriceListService $service): RedirectResponse
    {
        $service->save($brand->id, $request->toData(), array_map('intval', (array) $request->validated('channel_ids', [])), $priceList, (int) $request->validated('lock_version'));

        return back()->with('success', __('pricing::messages.saved'));
    }

    public function destroy(Brand $brand, PriceList $priceList, PriceListService $service): RedirectResponse
    {
        Gate::authorize('pricing.manage', [ScopeRef::brand($brand->id)]);
        $service->delete($priceList);

        return redirect()->route('admin.pricing.price-lists.index')->with('success', __('pricing::messages.deleted'));
    }

    /**
     * Lưới giá theo mã sản phẩm: mọi variant của style với giá hiện tại trong bảng giá.
     */
    public function prices(Brand $brand, PriceList $priceList, Request $request, VariantDirectory $variants): Response
    {
        Gate::authorize('pricing.view', [ScopeRef::brand($brand->id)]);

        $styleCode = trim((string) $request->query('style', ''));
        $rows = $styleCode === '' ? [] : $variants->ofStyleCode($brand->id, $styleCode);
        $prices = Price::query()->where('price_list_id', $priceList->id)->where('min_qty', 1)
            ->whereIn('variant_id', array_map(fn ($variant): int => $variant->id, $rows))->get()->keyBy('variant_id');

        return Inertia::render('Pricing::PriceLists/Prices', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'baseUrl' => route('admin.pricing.price-lists.index'),
            'priceList' => ['id' => $priceList->id, 'code' => $priceList->code, 'name' => $priceList->name, 'type' => $priceList->type->value],
            'styleCode' => $styleCode,
            'styleName' => $rows[0]->styleName ?? null,
            'rows' => array_map(fn ($variant): array => [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'color_code' => $variant->colorCode,
                'size_code' => $variant->sizeCode,
                'status' => $variant->status,
                'amount' => $prices->get($variant->id)?->amount,
                'compare_at_amount' => $prices->get($variant->id)?->compare_at_amount,
            ], $rows),
            'canManage' => Gate::allows('pricing.manage', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function updatePrices(Brand $brand, PriceList $priceList, PricesRequest $request, PriceListService $service): RedirectResponse
    {
        $count = $service->setPrices($priceList, $request->rows());

        return back()->with('success', __('pricing::messages.prices_saved', ['count' => $count]));
    }

    private function form(Brand $brand, ?PriceList $list, ChannelDirectory $channels, PriceListQueries $queries): Response
    {
        return Inertia::render('Pricing::PriceLists/Form', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'baseUrl' => route('admin.pricing.price-lists.index'),
            'priceList' => $list === null ? null : [
                'id' => $list->id,
                'code' => $list->code,
                'name' => $list->name,
                'type' => $list->type->value,
                'priority' => $list->priority,
                'starts_at' => $list->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $list->ends_at?->format('Y-m-d\TH:i'),
                'status' => $list->status,
                'lock_version' => $list->lock_version,
                'channel_ids' => $queries->channelIds($list->id),
            ],
            'channels' => $channels->forBrand($brand->id),
            'types' => array_column(PriceListType::cases(), 'value'),
        ]);
    }
}
