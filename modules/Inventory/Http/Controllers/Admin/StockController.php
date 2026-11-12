<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Inventory\Application\LocationService;
use Modules\Inventory\Application\LowStockThreshold;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Application\StockBulkEditor;
use Modules\Inventory\Application\StockCsv;
use Modules\Inventory\Application\StockGrid;
use Modules\Inventory\Application\StockQueries;
use Modules\Inventory\Contracts\StockPageLink;
use Modules\Inventory\Http\Requests\StockChangeRequest;
use Modules\Inventory\Persistence\Models\Location;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StockController
{
    /** Sản phẩm mỗi trang (mọi variant của một sản phẩm luôn cùng trang). */
    private const PER_PAGE = 20;

    public function home(): RedirectResponse
    {
        Gate::authorize('inventory.view');

        return redirect()->route('admin.inventory.stock.index');
    }

    /**
     * Lưới tồn: sản phẩm × kho (tìm theo tên/mã/SKU, lọc màu, size, hết hàng / sắp hết), sửa số trong ô, chọn nhiều để sửa
     * hàng loạt. `?style=` (link từ form sản phẩm) = đúng các variant của mã sản phẩm đó.
     */
    public function index(Request $request, StockGrid $grid, Extensions $extensions, LowStockThreshold $lowStock): Response
    {
        Gate::authorize('inventory.view');

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', Rule::in([20, 50, 100])],
        ]);
        $perPage = (int) ($validated['per_page'] ?? self::PER_PAGE);
        $criteria = $this->criteria($request->query());
        $page = max(1, (int) $request->query('page', 1));
        $result = $grid->page($criteria, $page, $perPage);
        $canManageLocations = Gate::allows('inventory.locations.manage', [ScopeRef::owner()]);
        // Nút của plugin (vd. vận hành kho — ADR-042): chỉ implementation của plugin đang bật.
        $links = $extensions->implementations(StockPageLink::TAG, StockPageLink::class, fn (StockPageLink $link): string => $link::class);
        $managed = array_filter($links, fn (StockPageLink $link): bool => $link->managesLocations());
        $online = array_filter($result['locations'], fn (Location $location): bool => $location->status === 'active' && $location->ships_online_orders);

        return Inertia::render('Inventory::Stock/Index', [
            'labels' => __('inventory::labels'),
            'urls' => [
                'base' => route('admin.inventory.stock.index'),
                'batch' => route('admin.inventory.stock.batch'),
                'bulk' => route('admin.inventory.stock.bulk'),
                'change' => route('admin.inventory.stock.change'),
                'export' => route('admin.inventory.stock.export'),
                'import' => route('admin.inventory.stock.import'),
                'movements' => route('admin.inventory.movements.index'),
                // Danh mục tỉnh/thành cho form Kho chính (AddressDirectory của plugin, qua Storefront API); không có → nhập mã tay.
                'provinces' => url('/api/storefront/v1/address/provinces'),
                'locations' => array_values(array_filter(array_map(fn (StockPageLink $link): ?string => $link->url(), $managed)))[0] ?? null,
                'main_location' => $managed === [] && $canManageLocations && count($result['locations']) === 1 ? route('admin.inventory.stock.location', $result['locations'][0]->id) : null,
                'default_location' => $canManageLocations && $result['locations'] === [] ? route('admin.inventory.stock.default-location') : null,
            ],
            'query' => $criteria,
            'options' => ['colors' => $result['colors'], 'sizes' => $result['sizes']],
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total_products'], 'variants' => $result['total_variants']],
            'threshold' => $lowStock->value(),
            'warnings' => ['no_location' => $result['locations'] === [], 'no_online_location' => $result['locations'] !== [] && $online === []],
            'links' => array_values(array_filter(array_map(fn (StockPageLink $link): ?array => ($url = $link->url()) === null ? null : ['label' => $link->label(), 'url' => $url], $links))),
            'mainLocation' => count($result['locations']) === 1 ? $result['locations'][0]->only(['id', 'name', 'address', 'province_code', 'lock_version']) : null,
            'locations' => array_map(fn (Location $location): array => [
                'id' => $location->id, 'code' => $location->code, 'name' => $location->name,
                'external' => ! $location->managesOnHand(), 'online' => $location->status === 'active' && $location->ships_online_orders,
            ], $result['locations']),
            'products' => $result['products'],
            'canAdjust' => Gate::allows('inventory.adjust'),
        ]);
    }

    /**
     * Sửa hàng loạt tại một kho: các SKU đã chọn (`variant_ids`) hoặc mọi SKU khớp bộ lọc (`all` + điều kiện lọc).
     */
    public function bulk(Request $request, StockGrid $grid, StockBulkEditor $editor, StockQueries $queries): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $data = $request->validate([
            'location_id' => ['required', 'integer'],
            'action' => ['required', Rule::in(StockBulkEditor::ACTIONS)],
            'quantity' => ['required', 'integer', 'min:-100000', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn (): bool => $request->input('action') === 'adjust')],
            'all' => ['boolean'],
            'variant_ids' => ['exclude_if:all,true', 'required', 'array', 'min:1', 'max:'.StockBulkEditor::MAX_VARIANTS],
            'variant_ids.*' => ['integer'],
            'criteria' => ['nullable', 'array'],
        ]);
        if ($data['action'] !== 'adjust' && $data['quantity'] < 0) {
            throw ValidationException::withMessages(['quantity' => __('inventory::messages.bulk_negative')]);
        }
        $location = collect($queries->locations())->firstWhere('id', (int) $data['location_id']);
        abort_if($location === null, 404);

        $ids = ($data['all'] ?? false) ? $grid->variantIds($this->criteria((array) ($data['criteria'] ?? []))) : array_map('intval', $data['variant_ids']);
        $reason = trim((string) ($data['reason'] ?? '')) ?: __('inventory::messages.bulk_reason');
        $result = $editor->apply($location, $ids, $data['action'], (int) $data['quantity'], $reason);

        $response = back()->with('success', __('inventory::messages.bulk_done', ['updated' => $result['updated'], 'unchanged' => $result['unchanged'], 'location' => $location->code]));

        return $result['skipped'] === 0 ? $response : $response->withErrors(['changes' => __('inventory::messages.bulk_skipped', ['count' => $result['skipped']])]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{q: string, style: string, filter: string, color: string, size: string}
     */
    private function criteria(array $input): array
    {
        $filter = (string) ($input['filter'] ?? 'all');

        return [
            'q' => trim((string) ($input['q'] ?? '')), 'style' => trim((string) ($input['style'] ?? '')),
            'filter' => in_array($filter, StockGrid::FILTERS, true) ? $filter : 'all',
            'color' => trim((string) ($input['color'] ?? '')), 'size' => trim((string) ($input['size'] ?? '')),
        ];
    }

    /**
     * Lưu nhiều ô tồn một lần (như kiểm kê). Ô mà tồn đã đổi sau khi mở trang (`expected` khác) không bị ghi đè.
     */
    public function batch(Request $request, StockAdjustmentService $stock, StockQueries $queries): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $data = $request->validate([
            'changes' => ['required', 'array', 'min:1', 'max:500'],
            'changes.*.location_id' => ['required', 'integer'],
            'changes.*.variant_id' => ['required', 'integer'],
            'changes.*.on_hand' => ['required', 'integer', 'min:0', 'max:1000000'],
            'changes.*.expected' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $locations = collect($queries->locations())->keyBy('id');
        $reason = trim((string) ($data['reason'] ?? '')) ?: __('inventory::messages.batch_reason');
        $updated = 0;
        $conflicts = 0;
        foreach ($data['changes'] as $change) {
            $location = $locations->get((int) $change['location_id']);
            abort_if($location === null, 404);
            $outcome = $stock->count($location, (int) $change['variant_id'], (int) $change['on_hand'], $reason, (int) $change['expected']);
            if ($outcome === StockAdjustmentService::CONFLICT) {
                $conflicts++;
            } elseif ($outcome === StockAdjustmentService::UPDATED) {
                $updated++;
            }
        }

        $response = back()->with('success', __('inventory::messages.batch_saved', ['updated' => $updated]));

        return $conflicts === 0 ? $response : $response->withErrors(['changes' => __('inventory::messages.batch_conflicts', ['count' => $conflicts])]);
    }

    /**
     * Cửa hàng chưa có kho → tạo "Kho chính" (nút ở trang tồn kho).
     */
    /**
     * Kho chính của cửa hàng một kho (ADR-042): chỉ tên, địa chỉ, tỉnh. Loại kho, khả năng, ưu tiên, authority ngoài và
     * thêm kho thuộc plugin vani.warehouse.
     */
    public function updateLocation(int $location, Request $request, LocationService $locations): RedirectResponse
    {
        Gate::authorize('inventory.locations.manage', [ScopeRef::owner()]);
        $record = Location::query()->findOrFail($location);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'province_code' => ['nullable', 'string', 'max:16'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ]);
        $locations->save(collect($data)->except('lock_version')->all(), $record, (int) $data['lock_version']);

        return back()->with('success', __('inventory::messages.saved'));
    }

    public function defaultLocation(LocationService $locations): RedirectResponse
    {
        Gate::authorize('inventory.locations.manage', [ScopeRef::owner()]);
        $created = $locations->ensureDefault();

        return back()->with('success', $created === null ? __('inventory::messages.default_exists') : __('inventory::messages.default_created', ['name' => $created->name]));
    }

    public function export(Request $request, StockCsv $csv, StockQueries $queries): StreamedResponse
    {
        Gate::authorize('inventory.view');
        $location = $request->filled('location') ? collect($queries->locations())->firstWhere('code', (string) $request->query('location')) : null;

        return response()->streamDownload(function () use ($csv, $location): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF"); // Excel đọc đúng tiếng Việt
            foreach ($csv->export($location) as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, 'ton-kho-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Tải file CSV → trang xem trước chênh lệch (chưa ghi gì).
     */
    public function import(Request $request, StockCsv $csv, StockQueries $queries): Response
    {
        Gate::authorize('inventory.adjust');
        $data = $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
            'location_id' => ['nullable', 'integer'],
        ]);
        $location = isset($data['location_id']) ? collect($queries->locations())->firstWhere('id', (int) $data['location_id']) : null;
        $file = $request->file('file');
        $preview = $csv->preview($file->getRealPath(), $file->getClientOriginalName(), $location);

        return Inertia::render('Inventory::Stock/Import', [
            'preview' => $preview,
            'statusLabels' => __('inventory::labels.import_status'),
            'urls' => ['back' => route('admin.inventory.stock.index'), 'apply' => $preview['token'] === null ? null : route('admin.inventory.stock.import.apply', $preview['token'])],
        ]);
    }

    public function applyImport(string $token, StockCsv $csv): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $result = $csv->apply($token);
        $response = redirect()->route('admin.inventory.stock.index')->with('success', __('inventory::messages.import_applied', ['updated' => $result['updated']]));

        return $result['conflicts'] === 0 ? $response : $response->withErrors(['changes' => __('inventory::messages.import_conflicts', ['count' => $result['conflicts']])]);
    }

    public function change(StockChangeRequest $request, VariantDirectory $variants, StockAdjustmentService $stock, StockQueries $queries): RedirectResponse
    {
        $variantId = (int) $request->validated('variant_id');
        $variant = $variants->find([$variantId])[$variantId] ?? null;
        $location = collect($queries->locations())->firstWhere('id', (int) $request->validated('location_id'));
        abort_if($variant === null || $location === null, 404);

        $quantity = (int) $request->validated('quantity');
        $reason = (string) $request->validated('reason');

        match ((string) $request->validated('action')) {
            'adjust' => $stock->adjust($location, $variantId, $quantity, $reason),
            'count' => $stock->count($location, $variantId, $quantity, $reason),
            'safety' => $stock->setSafetyStock($location, $variantId, max(0, $quantity)),
        };

        return back()->with('success', __('inventory::messages.adjusted'));
    }

    public function movements(Request $request, VariantDirectory $variants, StockQueries $queries): Response
    {
        Gate::authorize('inventory.view');

        $variantId = (int) $request->query('variant');
        $variant = $variants->find([$variantId])[$variantId] ?? null;
        abort_if($variant === null, 404);

        return Inertia::render('Inventory::Stock/Movements', [
            'backUrl' => route('admin.inventory.stock.index', ['style' => $variant->styleCode]),
            'variant' => ['sku' => $variant->sku, 'style_code' => $variant->styleCode],
            'movements' => $queries->movements($variantId),
        ]);
    }
}
