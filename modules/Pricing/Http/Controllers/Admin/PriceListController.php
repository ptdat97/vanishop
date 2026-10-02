<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Http\Requests\PriceListRequest;
use Modules\Pricing\Http\Requests\PricesRequest;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;

final class PriceListController
{
    public function home(): RedirectResponse
    {
        Gate::authorize('pricing.view');

        return redirect()->route('admin.pricing.price-lists.index');
    }

    public function index(): Response
    {
        Gate::authorize('pricing.view');

        $lists = PriceList::query()->withCount('prices')->orderByDesc('priority')->orderBy('code')->get();

        return Inertia::render('Pricing::PriceLists/Index', [
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
                ])->all(),
            'canManage' => Gate::allows('pricing.manage'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('pricing.manage');

        return $this->form(null);
    }

    public function store(PriceListRequest $request, PriceListService $service): RedirectResponse
    {
        $list = $service->save($request->toData());

        return redirect()->route('admin.pricing.price-lists.edit', ['priceList' => $list->id])->with('success', __('pricing::messages.saved'));
    }

    public function edit(PriceList $priceList): Response
    {
        Gate::authorize('pricing.manage');

        return $this->form($priceList);
    }

    public function update(PriceList $priceList, PriceListRequest $request, PriceListService $service): RedirectResponse
    {
        $service->save($request->toData(), $priceList, (int) $request->validated('lock_version'));

        return back()->with('success', __('pricing::messages.saved'));
    }

    public function destroy(PriceList $priceList, PriceListService $service): RedirectResponse
    {
        Gate::authorize('pricing.manage');
        $service->delete($priceList);

        return redirect()->route('admin.pricing.price-lists.index')->with('success', __('pricing::messages.deleted'));
    }

    /**
     * Lưới giá theo mã sản phẩm: mọi variant của style với giá hiện tại trong bảng giá.
     */
    public function prices(PriceList $priceList, Request $request, VariantDirectory $variants): Response
    {
        Gate::authorize('pricing.view');

        $styleCode = trim((string) $request->query('style', ''));
        $rows = $styleCode === '' ? [] : $variants->ofStyleCode($styleCode);
        $prices = Price::query()->where('price_list_id', $priceList->id)->where('min_qty', 1)
            ->whereIn('variant_id', array_map(fn ($variant): int => $variant->id, $rows))->get()->keyBy('variant_id');

        return Inertia::render('Pricing::PriceLists/Prices', [
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
            'canManage' => Gate::allows('pricing.manage'),
        ]);
    }

    public function updatePrices(PriceList $priceList, PricesRequest $request, PriceListService $service): RedirectResponse
    {
        $count = $service->setPrices($priceList, $request->rows());

        return back()->with('success', __('pricing::messages.prices_saved', ['count' => $count]));
    }

    private function form(?PriceList $list): Response
    {
        return Inertia::render('Pricing::PriceLists/Form', [
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
            ],
            'types' => array_column(PriceListType::cases(), 'value'),
        ]);
    }
}
