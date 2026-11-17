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
use Modules\Inventory\Application\StockReceiptService;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockReceipt;
use Modules\Shared\Support\SpreadsheetRows;
use Modules\Shared\Support\StoreClock;
use RuntimeException;

/**
 * Phiếu nhập nhanh (stabilization A6): danh sách, tạo/sửa nháp (gõ/quét SKU, dán nhiều dòng, tải file), nhập kho, huỷ.
 */
final class StockReceiptController
{
    public function __construct(private readonly AdminQueries $queries) {}

    public function index(Request $request): Response
    {
        Gate::authorize('inventory.view');
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in([StockReceipt::DRAFT, StockReceipt::RECEIVED, StockReceipt::CANCELLED])]]);
        $page = $this->queries->receipts(trim((string) ($data['q'] ?? '')), $data['status'] ?? null, 30);

        return Inertia::render('Inventory::Receipts/Index', [
            'filters' => ['q' => $data['q'] ?? '', 'status' => $data['status'] ?? null],
            'statusLabels' => __('inventory::labels.receipt_status'),
            'receipts' => [
                'data' => $page->getCollection()->map(fn (StockReceipt $receipt): array => [
                    'id' => $receipt->id, 'number' => $receipt->number, 'location' => $receipt->location?->name, 'supplier' => $receipt->supplier,
                    'lines' => (int) $receipt->getAttribute('lines_count'), 'units' => (int) $receipt->getAttribute('lines_sum_quantity'), 'status' => $receipt->status,
                    'created_at' => StoreClock::format($receipt->created_at), 'received_at' => StoreClock::format($receipt->received_at),
                ])->all(),
                'current' => $page->currentPage(), 'last' => $page->lastPage(), 'from' => $page->firstItem(), 'to' => $page->lastItem(), 'total' => $page->total(), 'per_page' => $page->perPage(),
            ],
            'canManage' => Gate::allows('inventory.adjust'),
            'urls' => ['base' => route('admin.inventory.receipts.index'), 'create' => route('admin.inventory.receipts.create'), 'stock' => route('admin.inventory.stock.index')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('inventory.adjust');

        return $this->form(null);
    }

    public function store(Request $request, StockReceiptService $receipts): RedirectResponse
    {
        Gate::authorize('inventory.adjust');

        return $this->save($request, $receipts, null);
    }

    public function show(StockReceipt $receipt): Response
    {
        Gate::authorize('inventory.view');

        return $this->form($receipt);
    }

    public function update(Request $request, StockReceipt $receipt, StockReceiptService $receipts): RedirectResponse
    {
        Gate::authorize('inventory.adjust');

        return $this->save($request, $receipts, $receipt);
    }

    public function receive(StockReceipt $receipt, StockReceiptService $receipts): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $receipts->receive($receipt);

        return redirect()->route('admin.inventory.receipts.show', $receipt)->with('success', __('inventory::messages.receipt_received', ['number' => $receipt->number, 'units' => (int) $receipt->lines()->sum('quantity')]));
    }

    public function cancel(StockReceipt $receipt, StockReceiptService $receipts): RedirectResponse
    {
        Gate::authorize('inventory.adjust');
        $receipts->cancel($receipt);

        return redirect()->route('admin.inventory.receipts.show', $receipt)->with('success', __('inventory::messages.receipt_cancelled', ['number' => $receipt->number]));
    }

    /**
     * Đọc dòng từ chữ dán/quét (`text`) hoặc file (.xlsx/.csv) → dòng phiếu (chưa lưu) để form thêm vào.
     */
    public function parse(Request $request, StockReceiptService $receipts, VariantDirectory $variants): JsonResponse
    {
        Gate::authorize('inventory.adjust');
        $request->validate(['text' => ['nullable', 'string', 'max:100000'], 'file' => ['nullable', 'file', 'max:2048', 'extensions:xlsx,csv,txt']]);
        if ($request->hasFile('file')) {
            try {
                $rows = SpreadsheetRows::fromUpload($request->file('file'));
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages(['file' => $exception->getMessage()]);
            }
        } else {
            $rows = array_map(fn (string $line): array => preg_split('/\s*[\t,;]\s*|\s{2,}|\s+(?=\d+$)/', trim($line)) ?: [], preg_split('/\r\n|\n|\r/', (string) $request->input('text', '')) ?: []);
        }
        $result = $receipts->parseRows($rows);

        return response()->json(['lines' => $this->lines($result['lines'], $variants), 'errors' => $result['errors']]);
    }

    private function save(Request $request, StockReceiptService $receipts, ?StockReceipt $receipt): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer'],
            'supplier' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1', 'max:'.StockReceiptService::MAX_LINES],
            'lines.*.variant_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'receive' => ['sometimes', 'boolean'],
            'lock_version' => [$receipt === null ? 'nullable' : 'required', 'integer', 'min:0'],
        ]);
        $location = collect($this->queries->managedLocations())->first(fn (Location $location): bool => $location->id === (int) $data['location_id'])
            ?? throw ValidationException::withMessages(['location_id' => __('validation.exists', ['attribute' => 'kho'])]);
        $lines = [];
        foreach ($data['lines'] as $line) {
            $lines[(int) $line['variant_id']] = ($lines[(int) $line['variant_id']] ?? 0) + (int) $line['quantity'];
        }
        $receipt = $receipts->saveDraft($receipt, $location, $data['supplier'] ?? null, $data['note'] ?? null, $lines, isset($data['lock_version']) ? (int) $data['lock_version'] : null);
        if ($request->boolean('receive')) {
            return $this->receive($receipt, $receipts);
        }

        return redirect()->route('admin.inventory.receipts.show', $receipt)->with('success', __('inventory::messages.receipt_saved', ['number' => $receipt->number]));
    }

    private function form(?StockReceipt $receipt): Response
    {
        $variants = app(VariantDirectory::class);
        $receipt?->load(['lines', 'location']);
        $quantities = $receipt === null ? [] : $receipt->lines->mapWithKeys(fn ($line): array => [$line->variant_id => $line->quantity])->all();

        return Inertia::render('Inventory::Receipts/Form', [
            'receipt' => $receipt === null ? null : [
                'id' => $receipt->id, 'number' => $receipt->number, 'lock_version' => $receipt->lock_version, 'location_id' => $receipt->location_id, 'location' => $receipt->location?->name,
                'supplier' => $receipt->supplier, 'note' => $receipt->note, 'status' => $receipt->status,
                'received_at' => StoreClock::format($receipt->received_at), 'lines' => $this->lines($quantities, $variants),
            ],
            'locations' => array_map(fn (Location $location): array => ['id' => $location->id, 'label' => "{$location->code} — {$location->name}"], $this->queries->managedLocations()),
            'statusLabels' => __('inventory::labels.receipt_status'),
            'canManage' => Gate::allows('inventory.adjust'),
            'urls' => [
                'index' => route('admin.inventory.receipts.index'),
                'save' => $receipt === null ? route('admin.inventory.receipts.store') : route('admin.inventory.receipts.update', $receipt),
                'parse' => route('admin.inventory.receipts.parse'),
                'receive' => $receipt === null ? null : route('admin.inventory.receipts.receive', $receipt),
                'cancel' => $receipt === null ? null : route('admin.inventory.receipts.cancel', $receipt),
            ],
        ]);
    }

    /**
     * @param  array<int, int>  $quantities  variant id => số lượng
     * @return list<array{variant_id: int, sku: string, name: string, quantity: int}>
     */
    private function lines(array $quantities, VariantDirectory $variants): array
    {
        $found = $variants->find(array_keys($quantities));
        $lines = [];
        foreach ($quantities as $variantId => $quantity) {
            $variant = $found[$variantId] ?? null;
            $lines[] = ['variant_id' => $variantId, 'sku' => $variant->sku ?? "#{$variantId}", 'name' => $variant === null ? '' : trim($variant->styleName.' '.VariantOptionsText::label($variant->options)), 'quantity' => $quantity];
        }

        return $lines;
    }
}
