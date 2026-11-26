<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Application\AdminQueries;
use Modules\Inventory\Application\SkuQuantityRows;
use Modules\Inventory\Application\StockCountService;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockCount;
use Modules\Shared\Support\SpreadsheetRows;
use Modules\Shared\Support\StoreClock;
use RuntimeException;

/**
 * Kiểm kê nhanh (stabilization A7): mở phiên theo kho → quét/nhập số đếm (lưu ngay, nhiều người cùng đếm) → xem chênh lệch →
 * áp dụng một lần.
 */
final class StockCountController
{
    public function __construct(private readonly AdminQueries $queries) {}

    public function index(Request $request): Response
    {
        Gate::authorize('inventory.view');
        $status = $request->validate(['status' => ['nullable', Rule::in([StockCount::COUNTING, StockCount::APPLIED, StockCount::CANCELLED])]])['status'] ?? null;
        $page = $this->queries->counts($status, 30);

        return Inertia::render('Inventory::Counts/Index', [
            'filters' => ['status' => $status],
            'statusLabels' => __('inventory::labels.count_status'),
            'counts' => [
                'data' => $page->getCollection()->map(fn (StockCount $count): array => [
                    'id' => $count->id, 'number' => $count->number, 'location' => $count->location?->name, 'note' => $count->note,
                    'lines' => (int) $count->getAttribute('lines_count'), 'status' => $count->status,
                    'created_at' => StoreClock::format($count->created_at), 'applied_at' => StoreClock::format($count->applied_at),
                ])->all(),
                'current' => $page->currentPage(), 'last' => $page->lastPage(), 'from' => $page->firstItem(), 'to' => $page->lastItem(), 'total' => $page->total(), 'per_page' => $page->perPage(),
            ],
            'locations' => array_map(fn (Location $location): array => ['id' => $location->id, 'label' => "{$location->code} — {$location->name}"], $this->queries->managedLocations()),
            'canManage' => Gate::allows('inventory.adjust'),
            'urls' => ['base' => route('admin.inventory.counts.index'), 'store' => route('admin.inventory.counts.store'), 'stock' => route('admin.inventory.stock.index')],
        ]);
    }

    public function store(Request $request, StockCountService $counts): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $data = $request->validate(['location_id' => ['required', 'integer'], 'note' => ['nullable', 'string', 'max:500']]);
        $location = collect($this->queries->managedLocations())->first(fn (Location $location): bool => $location->id === (int) $data['location_id'])
            ?? throw ValidationException::withMessages(['location_id' => __('validation.exists', ['attribute' => 'kho'])]);
        $count = $counts->start($location, $data['note'] ?? null);

        return redirect()->route('admin.inventory.counts.show', $count)->with('success', __('inventory::messages.count_started', ['number' => $count->number]));
    }

    public function show(StockCount $count, StockCountService $counts, VariantDirectory $variants): Response
    {
        Gate::authorize('inventory.view');
        $count->load('location');
        $preview = $counts->preview($count);
        $found = $variants->find(array_column($preview, 'variant_id'));
        $applied = $count->status === StockCount::APPLIED ? $count->lines()->pluck('applied_delta', 'variant_id') : null;

        return Inertia::render('Inventory::Counts/Show', [
            'count' => [
                'id' => $count->id, 'number' => $count->number, 'location' => $count->location?->name, 'note' => $count->note, 'status' => $count->status,
                'created_at' => StoreClock::format($count->created_at), 'applied_at' => StoreClock::format($count->applied_at),
            ],
            'lines' => array_map(function (array $line) use ($found, $applied): array {
                $variant = $found[$line['variant_id']] ?? null;

                return [...$line,
                    'sku' => $variant->sku ?? "#{$line['variant_id']}",
                    'name' => $variant === null ? '' : trim($variant->productName.' '.VariantOptionsText::label($variant->options)),
                    'applied_delta' => $applied === null ? null : (int) ($applied[$line['variant_id']] ?? 0),
                ];
            }, $preview),
            'statusLabels' => __('inventory::labels.count_status'),
            'canManage' => Gate::allows('inventory.adjust') && $count->status === StockCount::COUNTING,
            'urls' => [
                'index' => route('admin.inventory.counts.index'),
                'record' => route('admin.inventory.counts.record', $count),
                'line' => route('admin.inventory.counts.line', [$count, 0]),
                'apply' => route('admin.inventory.counts.apply', $count),
                'cancel' => route('admin.inventory.counts.cancel', $count),
            ],
        ]);
    }

    /**
     * Thêm số đếm từ ô quét/dán (`text`) hoặc file — cộng thêm vào số đã đếm.
     */
    public function record(Request $request, StockCount $count, StockCountService $counts, SkuQuantityRows $parser): RedirectResponse|JsonResponse
    {
        Gate::authorize('inventory.adjust');
        $request->validate(['text' => ['nullable', 'string', 'max:100000'], 'file' => ['nullable', 'file', 'max:2048', 'extensions:xlsx,csv,txt']]);
        try {
            $rows = $request->hasFile('file') ? SpreadsheetRows::fromUpload($request->file('file')) : SkuQuantityRows::fromText((string) $request->input('text', ''));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
        $result = $parser->parse($rows);
        if ($result['lines'] !== []) {
            $counts->record($count, $result['lines'], add: true);
        }
        if ($request->expectsJson()) {
            // Ô quét gọi bằng JSON theo hàng đợi (quét nhanh không làm mất mã), rồi tự tải lại danh sách dòng.
            return response()->json(['recorded' => count($result['lines']), 'errors' => $result['errors']]);
        }
        $response = back()->with('success', __('inventory::messages.count_recorded', ['count' => count($result['lines'])]));

        return $result['errors'] === [] ? $response : $response->withErrors(array_combine(array_map(fn (int $index): string => "parse.{$index}", array_keys(array_slice($result['errors'], 0, 20))), array_slice($result['errors'], 0, 20)));
    }

    /**
     * Sửa số đếm của một SKU (`counted`) hoặc bỏ SKU khỏi phiên (`counted` rỗng).
     */
    public function line(Request $request, StockCount $count, int $variant, StockCountService $counts): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $data = $request->validate(['counted' => ['nullable', 'integer', 'min:0', 'max:1000000']]);
        isset($data['counted']) ? $counts->record($count, [$variant => (int) $data['counted']], add: false) : $counts->removeLine($count, $variant);

        return back();
    }

    public function apply(StockCount $count, StockCountService $counts): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $result = $counts->apply($count);

        return redirect()->route('admin.inventory.counts.show', $count)->with('success', __('inventory::messages.count_applied', ['number' => $count->number, ...$result]));
    }

    public function cancel(StockCount $count, StockCountService $counts): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $counts->cancel($count);

        return redirect()->route('admin.inventory.counts.show', $count)->with('success', __('inventory::messages.count_cancelled', ['number' => $count->number]));
    }
}
