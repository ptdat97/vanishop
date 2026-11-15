<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Application\BulkBasePrice;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Application\PriceListWorkspace;
use Modules\Pricing\Contracts\CustomerGroupDirectory;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Http\Requests\BulkPricesRequest;
use Modules\Pricing\Http\Requests\PriceListRequest;
use Modules\Pricing\Http\Requests\PricesRequest;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Support\StoreClock;

final class PriceListController
{
    /** Số SKU mỗi trang ở trang làm việc bảng giá (mặc định là giá trị đầu). */
    private const PER_PAGE_OPTIONS = [50, 100, 200];

    public function home(): RedirectResponse
    {
        Gate::authorize('pricing.view');

        return redirect()->route('admin.pricing.price-lists.index');
    }

    public function index(): Response
    {
        Gate::authorize('pricing.view');

        $lists = PriceList::query()->withCount('prices')->orderByDesc('priority')->orderBy('code')->get();
        $groupNames = array_column(app(CustomerGroupDirectory::class)->groups(), 'name', 'id');

        return Inertia::render('Pricing::PriceLists/Index', [
            'baseUrl' => route('admin.pricing.price-lists.index'),
            'labels' => __('pricing::labels'),
            'priceLists' => $lists
                ->map(fn (PriceList $list): array => [
                    'id' => $list->id,
                    'code' => $list->code,
                    'name' => $list->name,
                    'type' => $list->type->value,
                    'customer_group' => $groupNames[$list->customer_group_id] ?? null,
                    'priority' => $list->priority,
                    'status' => $list->status,
                    'state' => PriceListWorkspace::state($list),
                    'starts_at' => StoreClock::format($list->starts_at),
                    'ends_at' => StoreClock::format($list->ends_at),
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
     * Trang làm việc của bảng giá (đợt 1): mọi SKU trong bảng (tìm, phân trang), sửa giá trong ô, chọn nhiều để áp giá
     * hàng loạt hoặc gỡ; thêm sản phẩm. `?style=` (đường dẫn cũ) được hiểu như ô tìm.
     */
    public function prices(PriceList $priceList, Request $request, PriceListWorkspace $workspace): Response
    {
        Gate::authorize('pricing.view');
        $text = trim((string) ($request->query('q') ?? $request->query('style', '')));
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE_OPTIONS, true) ? (int) $request->query('per_page') : self::PER_PAGE_OPTIONS[0];
        $page = $workspace->rows($priceList, $text, max(1, (int) $request->query('page', 1)), $perPage, route('admin.pricing.price-lists.prices', $priceList));

        return Inertia::render('Pricing::PriceLists/Prices', [
            'baseUrl' => route('admin.pricing.price-lists.index'),
            'labels' => __('pricing::labels'),
            'priceList' => [
                'id' => $priceList->id, 'code' => $priceList->code, 'name' => $priceList->name, 'type' => $priceList->type->value,
                'state' => PriceListWorkspace::state($priceList),
                'starts_at' => StoreClock::format($priceList->starts_at), 'ends_at' => StoreClock::format($priceList->ends_at),
            ],
            'q' => $text,
            'rows' => $page->items(),
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'from' => $page->firstItem(), 'to' => $page->lastItem(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'summary' => $workspace->summary($priceList),
            'capPercent' => intdiv($workspace->capBasisPoints(), 100),
            'canManage' => Gate::allows('pricing.manage'),
        ]);
    }

    /**
     * Tìm sản phẩm để thêm vào bảng giá (JSON cho hộp thoại "Thêm sản phẩm").
     */
    public function candidates(PriceList $priceList, Request $request, PriceListWorkspace $workspace): JsonResponse
    {
        Gate::authorize('pricing.manage');
        $text = (string) $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'];

        return response()->json(['data' => $workspace->candidates($priceList, $text)]);
    }

    public function previewBulk(PriceList $priceList, BulkPricesRequest $request, PriceListWorkspace $workspace): JsonResponse
    {
        return response()->json(['data' => $workspace->preview($priceList, $request->variantIds($priceList, $workspace), $request->rule())]);
    }

    public function applyBulk(PriceList $priceList, BulkPricesRequest $request, PriceListWorkspace $workspace): RedirectResponse
    {
        $result = $workspace->apply($priceList, $request->variantIds($priceList, $workspace), $request->rule());
        $message = __('pricing::messages.bulk_applied', ['count' => $result['changed']]);
        if ($result['skipped'] > 0) {
            $message .= ' '.__('pricing::messages.bulk_skipped', ['count' => $result['skipped']]);
        }

        return back()->with('success', $message);
    }

    public function removePrices(PriceList $priceList, Request $request, PriceListWorkspace $workspace): RedirectResponse
    {
        Gate::authorize('pricing.manage');
        $ids = (array) $request->validate(['variant_ids' => ['required', 'array', 'max:'.PriceListWorkspace::MAX_BULK], 'variant_ids.*' => ['integer', 'min:1']])['variant_ids'];

        return back()->with('success', __('pricing::messages.bulk_removed', ['count' => $workspace->remove($priceList, array_map('intval', $ids))]));
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
            'labels' => __('pricing::labels'),
            'priceList' => $list === null ? null : [
                'id' => $list->id,
                'code' => $list->code,
                'name' => $list->name,
                'type' => $list->type->value,
                'customer_group_id' => $list->customer_group_id,
                'priority' => $list->priority,
                'starts_at' => StoreClock::toInput($list->starts_at),
                'ends_at' => StoreClock::toInput($list->ends_at),
                'status' => $list->status,
                'lock_version' => $list->lock_version,
            ],
            'types' => array_column(PriceListType::cases(), 'value'),
            'customerGroups' => app(CustomerGroupDirectory::class)->groups(),
        ]);
    }

    /**
     * Đổi giá niêm yết nhiều sản phẩm (danh sách sản phẩm → "Đổi giá", 0.3.103). `ids` là style id.
     */
    public function bulkBasePrice(Request $request, BulkBasePrice $prices): RedirectResponse
    {
        Gate::authorize('pricing.manage');
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'min:1'],
            'mode' => ['required', Rule::in(BulkBasePrice::MODES)],
            'value' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'strike' => ['nullable', 'boolean'],
        ]);
        $result = $prices->apply(array_values(array_unique(array_map('intval', $data['ids']))), $data['mode'], (int) $data['value'], (bool) ($data['strike'] ?? false));

        return back()->with('success', __('pricing::messages.bulk_prices_changed', $result));
    }

    /**
     * Đặt nhanh giá niêm yết của mọi size cho một sản phẩm từ danh sách sản phẩm.
     */
    public function setProductBasePrice(int $styleId, Request $request, BulkBasePrice $prices, VariantDirectory $variants): RedirectResponse
    {
        Gate::authorize('pricing.manage');
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ]);

        abort_unless(array_key_exists($styleId, $variants->ofStyles([$styleId])), 404);

        $result = $prices->apply([$styleId], 'set', (int) $data['amount'], false);

        return back()->with('success', __('pricing::messages.bulk_prices_changed', $result));
    }
}
