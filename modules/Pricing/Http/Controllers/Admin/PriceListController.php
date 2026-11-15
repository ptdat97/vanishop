<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Pricing\Application\BulkBasePrice;
use Modules\Pricing\Application\ListPriceSync;
use Modules\Pricing\Application\PriceListFile;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Application\PriceListWorkspace;
use Modules\Pricing\Contracts\CustomerGroupDirectory;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Http\Requests\BulkPricesRequest;
use Modules\Pricing\Http\Requests\PriceListRequest;
use Modules\Pricing\Http\Requests\PricesRequest;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Support\SimpleXlsx;
use Modules\Shared\Support\SpreadsheetRows;
use Modules\Shared\Support\StoreClock;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            // Dữ liệu cũ: bảng loại niêm yết ngoài bảng chính (mã `base`) — cần đổi sang Sale/Thành viên hoặc xoá.
            'extraBaseLists' => $lists->filter(fn (PriceList $list): bool => $list->type === PriceListType::Base && $list->code !== ListPriceSync::BASE_CODE)
                ->map(fn (PriceList $list): array => ['id' => $list->id, 'name' => $list->name, 'code' => $list->code])->values()->all(),
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

        // Tạo xong vào thẳng trang làm việc để thêm sản phẩm.
        return redirect()->route('admin.pricing.price-lists.prices', ['priceList' => $list->id])->with('success', __('pricing::messages.created'));
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
    public function prices(PriceList $priceList, Request $request, PriceListWorkspace $workspace, AdminScreen $screen): Response
    {
        Gate::authorize('pricing.view');
        $extensionFilters = array_filter((array) $request->query('ext', []), 'is_string');
        $text = trim((string) ($request->query('q') ?? $request->query('style', '')));
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE_OPTIONS, true) ? (int) $request->query('per_page') : self::PER_PAGE_OPTIONS[0];
        $page = $workspace->rows($priceList, $text, max(1, (int) $request->query('page', 1)), $perPage, route('admin.pricing.price-lists.prices', $priceList), $screen->filterIds('price_list_item', $extensionFilters));

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
            // Giá niêm yết sửa ở sản phẩm (form, ma trận, danh sách sản phẩm) — trang bảng giá chỉ xem.
            'readOnly' => $priceList->type === PriceListType::Base,
            'productsUrl' => route('admin.catalog.products.index'),
            // Cột/lọc/thao tác/công cụ của plugin theo SKU (EP v3 X1) — vd. cột giá vốn/biên lợi nhuận.
            'extensions' => $screen->listProps('price_list_item', array_column($page->items(), 'variant_id'), $extensionFilters),
            'skuPickerFilters' => $screen->filters('variant'),
            // Cách tính / làm tròn của plugin cho "Áp giá…" (EP v3 X4).
            'bulkOptions' => $workspace->pluginOptions(),
            // Từ danh sách sản phẩm "Đưa vào bảng giá…": sản phẩm đã chọn, mở sẵn hộp thoại cách tính giá.
            'addStyleIds' => $priceList->type === PriceListType::Base ? [] : $this->styleIdsFromQuery((string) $request->query('add', '')),
            'canManage' => Gate::allows('pricing.manage'),
        ]);
    }

    /**
     * Tìm sản phẩm để thêm vào bảng giá (JSON cho hộp thoại "Thêm sản phẩm").
     */
    /**
     * Danh sách sản phẩm → "Đưa vào bảng giá…" (slot vani.admin.product.bulk_actions): mở trang làm việc của bảng giá đã chọn
     * với các sản phẩm đó, hộp thoại cách tính giá mở sẵn (xem trước trước khi áp).
     */
    public function addProducts(Request $request): RedirectResponse
    {
        Gate::authorize('pricing.manage');
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'min:1'],
            'price_list_id' => ['required', 'integer', Rule::exists('price_lists', 'id')->whereNot('type', PriceListType::Base->value)],
        ]);

        return redirect()->route('admin.pricing.price-lists.prices', ['priceList' => (int) $data['price_list_id'], 'add' => implode(',', array_unique(array_map('intval', $data['ids'])))]);
    }

    public function candidates(PriceList $priceList, Request $request, PriceListWorkspace $workspace, AdminScreen $screen): JsonResponse
    {
        Gate::authorize('pricing.manage');
        $this->ensureEditable($priceList);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'ext' => ['nullable', 'array']]);
        $only = $screen->filterIds('variant', array_filter((array) ($data['ext'] ?? []), 'is_string'));

        return response()->json(['data' => $workspace->candidates($priceList, (string) ($data['q'] ?? ''), $only)]);
    }

    public function previewBulk(PriceList $priceList, BulkPricesRequest $request, PriceListWorkspace $workspace): JsonResponse
    {
        $this->ensureEditable($priceList);

        return response()->json(['data' => $workspace->preview($priceList, $request->variantIds($priceList, $workspace), $request->rule(), $request->pluginMode(), $request->pluginRounding(), $request->value())]);
    }

    public function applyBulk(PriceList $priceList, BulkPricesRequest $request, PriceListWorkspace $workspace): RedirectResponse
    {
        $this->ensureEditable($priceList);
        $result = $workspace->apply($priceList, $request->variantIds($priceList, $workspace), $request->rule(), $request->pluginMode(), $request->pluginRounding(), $request->value());
        $message = __('pricing::messages.bulk_applied', ['count' => $result['changed']]);
        if ($result['skipped'] > 0) {
            $message .= ' '.__('pricing::messages.bulk_skipped', ['count' => $result['skipped']]);
        }

        return back()->with('success', $message);
    }

    public function removePrices(PriceList $priceList, Request $request, PriceListWorkspace $workspace): RedirectResponse
    {
        Gate::authorize('pricing.manage');
        $this->ensureEditable($priceList);
        $ids = (array) $request->validate(['variant_ids' => ['required', 'array', 'max:'.PriceListWorkspace::MAX_BULK], 'variant_ids.*' => ['integer', 'min:1']])['variant_ids'];

        return back()->with('success', __('pricing::messages.bulk_removed', ['count' => $workspace->remove($priceList, array_map('intval', $ids))]));
    }

    /**
     * Xuất bảng giá ra Excel — cũng là mẫu để nhập lại (sửa cột Giá / Giá gạch).
     */
    public function export(PriceList $priceList, PriceListFile $file): BinaryFileResponse
    {
        Gate::authorize('pricing.view');
        $path = tempnam(sys_get_temp_dir(), 'vani-price-list-').'.xlsx';
        SimpleXlsx::write($path, $file->export($priceList), 'Bang gia');

        return response()->download($path, "bang-gia-{$priceList->code}-".now(StoreClock::timezone())->format('Ymd-Hi').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Nhập giá từ file (.xlsx/.csv): cột SKU + Giá (+ Giá gạch). Dòng lỗi được báo, dòng hợp lệ vẫn được ghi.
     */
    public function import(PriceList $priceList, Request $request, PriceListFile $file): RedirectResponse
    {
        Gate::authorize('pricing.manage');
        $this->ensureEditable($priceList);
        $request->validate(['file' => ['required', 'file', 'max:5120', 'extensions:xlsx,csv,txt']]);
        try {
            $rows = SpreadsheetRows::fromUpload($request->file('file'));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
        $result = $file->import($priceList, $rows);
        $redirect = back()->with('success', __('pricing::messages.imported', ['changed' => $result['changed'], 'unchanged' => $result['unchanged'], 'errors' => count($result['errors'])]));

        // Mỗi dòng lỗi một khoá (Inertia chỉ chuyển câu đầu tiên của mỗi khoá).
        $errors = [];
        foreach (array_slice($result['errors'], 0, 30) as $index => $message) {
            $errors["import.{$index}"] = $message;
        }

        return $errors === [] ? $redirect : $redirect->withErrors($errors);
    }

    public function updatePrices(PriceList $priceList, PricesRequest $request, PriceListService $service): RedirectResponse
    {
        $this->ensureEditable($priceList);
        $count = $service->setPrices($priceList, $request->rows());

        return back()->with('success', __('pricing::messages.prices_saved', ['count' => $count]));
    }

    /**
     * `?add=1,2,3` (từ "Đưa vào bảng giá…") → id sản phẩm, tối đa 200.
     *
     * @return list<int>
     */
    private function styleIdsFromQuery(string $value): array
    {
        $ids = array_filter(array_map('intval', explode(',', $value)), fn (int $id): bool => $id > 0);

        return array_slice(array_values(array_unique($ids)), 0, 200);
    }

    /**
     * Giá niêm yết chỉ sửa ở sản phẩm (một chỗ sửa): form/ma trận biến thể, sửa trong ô và "Đổi giá" ở danh sách sản phẩm.
     */
    private function ensureEditable(PriceList $list): void
    {
        if ($list->type === PriceListType::Base) {
            throw ValidationException::withMessages(['business' => __('pricing::messages.base_read_only')]);
        }
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
            // Chỉ bảng niêm yết chính là `base`; tạo mới / bảng `base` thừa (dữ liệu cũ) chọn Sale hoặc Thành viên.
            'types' => $list?->code === ListPriceSync::BASE_CODE ? [PriceListType::Base->value] : [PriceListType::Sale->value, PriceListType::Member->value],
            'defaultPriorities' => array_combine(array_column(PriceListType::cases(), 'value'), array_map(fn (PriceListType $type): int => $type->defaultPriority(), PriceListType::cases())),
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
