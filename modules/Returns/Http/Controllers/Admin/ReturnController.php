<?php

declare(strict_types=1);

namespace Modules\Returns\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Returns\Application\ReturnReasons;
use Modules\Returns\Application\ReturnService;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Domain\BusinessRuleViolation;
use Modules\Shared\Support\StoreClock;

final class ReturnController
{
    /** Số dòng mỗi trang (mặc định là giá trị đầu) — như danh sách đơn hàng. */
    private const PER_PAGE_OPTIONS = [30, 50, 100];

    public function __construct(private readonly ReturnReasons $reasons) {}

    public function home(): RedirectResponse
    {
        Gate::authorize('returns.view');

        return redirect()->route('admin.returns.returns.index');
    }

    public function index(Request $request, OrderReader $orders): Response
    {
        Gate::authorize('returns.view');
        $status = (string) $request->query('status', '');
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE_OPTIONS, true) ? (int) $request->query('per_page') : self::PER_PAGE_OPTIONS[0];
        $page = ReturnRequest::query()->withCount('lines')->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Returns::Returns/Index', [
            'labels' => $this->labels(),
            'baseUrl' => route('admin.returns.returns.index'),
            'reasonsUrl' => route('admin.returns.reasons.index'),
            'status' => $status,
            'statuses' => array_column(ReturnStatus::cases(), 'value'),
            // Số yêu cầu theo trạng thái trên các thẻ lọc (0.3.101).
            // Thao tác hàng loạt (0.3.102): duyệt; nhận hàng còn bán được hoặc hàng hỏng — yêu cầu không đúng trạng thái được bỏ qua.
            'actions' => Gate::allows('returns.manage') ? [
                ['key' => 'approve', 'label' => 'Duyệt', 'url' => route('admin.returns.returns.bulk-approve'), 'confirm' => true],
                ['key' => 'receive', 'label' => 'Đã nhận hàng (còn bán được)', 'url' => route('admin.returns.returns.bulk-receive'), 'confirm' => true],
                ['key' => 'receive_damaged', 'label' => 'Đã nhận hàng (hỏng, không nhập kho)', 'url' => route('admin.returns.returns.bulk-receive', ['condition' => 'damaged']), 'confirm' => true],
            ] : [],
            'counts' => ReturnRequest::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($total): int => (int) $total)->all(),
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'from' => $page->firstItem(), 'to' => $page->lastItem(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'returns' => collect($page->items())->map(fn (ReturnRequest $return): array => [
                'id' => $return->id, 'number' => $return->number, 'status' => $return->status->value, 'reason_code' => $return->reason_code,
                'refund_amount' => $return->refund_amount, 'source' => $return->source,
                'created_at' => StoreClock::format($return->created_at),
            ])->all(),
        ]);
    }

    /**
     * Nhân viên tạo yêu cầu đổi/trả hộ khách (khách gọi điện, nhắn tin): cùng chính sách + giới hạn số lượng như khách tự
     * tạo, nguồn `staff`.
     */
    public function create(int $order, ReturnService $returns, OrderReader $orders): Response
    {
        Gate::authorize('returns.manage');
        $data = $orders->find($order) ?? abort(404);
        $returnable = $returns->returnable($order);

        return Inertia::render('Returns::Returns/Create', [
            'labels' => $this->labels(),
            'storeUrl' => route('admin.returns.returns.store'),
            'orderUrl' => route('admin.orders.orders.show', ['order' => $order]),
            'order' => ['id' => $data->id, 'number' => $data->number],
            'deadline' => $returnable['deadline'],
            'lines' => array_map(fn ($line): array => [
                'id' => $line->id, 'sku' => $line->sku, 'name' => $line->productName, 'color' => $line->colorName, 'size' => $line->sizeCode,
                'ordered' => $line->quantity, 'returnable' => (int) ($returnable['lines'][$line->id] ?? 0),
            ], $orders->lines($order)),
            'reasons' => array_column($this->reasons->active(App::getLocale()), 'code'),
        ]);
    }

    public function store(Request $request, ReturnService $returns, OrderReader $orders, VariantDirectory $variants): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['integer', 'min:0', 'max:1000'],
            'reason_code' => ['required', 'string', Rule::in(array_column($this->reasons->active(App::getLocale()), 'code'))],
            'note' => ['nullable', 'string', 'max:500'],
            // Đổi hàng: SKU thay thế theo dòng (order_line_id => SKU).
            'exchange_skus' => ['array'],
            'exchange_skus.*' => ['nullable', 'string', 'max:64'],
        ]);
        abort_if($orders->find((int) $data['order_id']) === null, 404);

        $lines = [];
        foreach ($data['lines'] as $lineId => $quantity) {
            $lines[(int) $lineId] = (int) $quantity;
        }
        $skus = array_filter(array_map(fn (?string $sku): string => strtoupper(trim((string) $sku)), $data['exchange_skus'] ?? []), fn (string $sku): bool => $sku !== '');
        $skus = array_intersect_key($skus, array_filter($lines));
        $found = $skus === [] ? [] : $variants->findBySkus(array_values(array_unique($skus)));
        $exchanges = [];
        foreach ($skus as $lineId => $sku) {
            $exchanges[(int) $lineId] = $found[$sku]->id ?? throw ValidationException::withMessages(["exchange_skus.{$lineId}" => __('returns::messages.exchange_invalid.variant')]);
        }
        $view = $returns->request((int) $data['order_id'], $lines, $data['reason_code'], $data['note'] ?? null, 'staff', $exchanges);

        return redirect()->route('admin.returns.returns.show', ['return' => $view->id])->with('success', __('returns::messages.created'));
    }

    public function show(ReturnRequest $return, ReturnService $returns, OrderReader $orders): Response
    {
        Gate::authorize('returns.view');
        $canManage = Gate::allows('returns.manage');

        return Inertia::render('Returns::Returns/Show', [
            'labels' => $this->labels(),
            'baseUrl' => route('admin.returns.returns.index'),
            'orderUrl' => route('admin.orders.orders.show', ['order' => $return->order_id]),
            'orderNumber' => $orders->find($return->order_id)?->number,
            'return' => (array) $returns->view($return),
            'exchangeQuote' => $return->resolution === 'exchange' && $return->status->canMoveTo(ReturnStatus::Resolved) ? $returns->exchangeQuote($return->id) : null,
            'replacementOrder' => $return->replacement_order_id === null ? null : [
                'number' => $orders->find($return->replacement_order_id)?->number,
                'url' => route('admin.orders.orders.show', ['order' => $return->replacement_order_id]),
            ],
            'can' => [
                'approve' => $canManage && $return->status->canMoveTo(ReturnStatus::Approved),
                'reject' => $canManage && $return->status->canMoveTo(ReturnStatus::Rejected),
                'in_transit' => $canManage && $return->status->canMoveTo(ReturnStatus::InTransit),
                'receive' => $canManage && $return->status->canMoveTo(ReturnStatus::Received),
                'resolve' => Gate::allows('returns.refund') && $return->status->canMoveTo(ReturnStatus::Resolved),
            ],
        ]);
    }

    /**
     * Duyệt hàng loạt các yêu cầu đang chờ duyệt (0.3.102).
     */
    public function bulkApprove(Request $request, ReturnService $returns): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $done = 0;
        $ids = $this->bulkIds($request);
        foreach (ReturnRequest::query()->whereIn('id', $ids)->get() as $return) {
            if (! $return->status->canMoveTo(ReturnStatus::Approved)) {
                continue;
            }
            try {
                $returns->transition($return->id, ReturnStatus::Approved, null, 'staff', $return->lock_version);
                $done++;
            } catch (BusinessRuleViolation) {
                // Yêu cầu vừa đổi ở nơi khác: bỏ qua.
            }
        }

        return back()->with('success', __('returns::messages.bulk_approved', ['count' => $done, 'skipped' => count($ids) - $done]));
    }

    /**
     * Nhận hàng hàng loạt (0.3.102): mọi dòng còn bán được → nhập lại kho; `?condition=damaged` → mọi dòng là hàng hỏng,
     * không nhập kho. Yêu cầu vừa có dòng bán được vừa có dòng hỏng: nhận ở trang chi tiết.
     */
    public function bulkReceive(Request $request, ReturnService $returns): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $damaged = $request->validate(['condition' => ['nullable', Rule::in(['sellable', 'damaged'])]])['condition'] ?? 'sellable';
        $damaged = $damaged === 'damaged';
        $done = 0;
        $ids = $this->bulkIds($request);
        foreach (ReturnRequest::query()->with('lines')->whereIn('id', $ids)->get() as $return) {
            if (! $return->status->canMoveTo(ReturnStatus::Received)) {
                continue;
            }
            try {
                $returns->receive($return->id, $damaged ? array_fill_keys($return->lines->pluck('id')->all(), 'damaged') : [], null);
                $done++;
            } catch (BusinessRuleViolation) {
                // bỏ qua
            }
        }

        return back()->with('success', __($damaged ? 'returns::messages.bulk_received_damaged' : 'returns::messages.bulk_received', ['count' => $done, 'skipped' => count($ids) - $done]));
    }

    /**
     * @return list<int>
     */
    private function bulkIds(Request $request): array
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'min:1']]);

        return array_values(array_unique(array_map('intval', $data['ids'])));
    }

    public function transition(ReturnRequest $return, Request $request, ReturnService $returns): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $data = $request->validate([
            'to' => ['required', Rule::in(['approved', 'rejected', 'in_transit'])],
            'note' => ['nullable', 'required_if:to,rejected', 'string', 'max:255'],
            'lock_version' => ['required', 'integer'],
        ]);
        $returns->transition($return->id, ReturnStatus::from($data['to']), $data['note'] ?? null, 'staff', (int) $data['lock_version']);

        return back()->with('success', __('returns::messages.updated'));
    }

    public function receive(ReturnRequest $return, Request $request, ReturnService $returns): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $data = $request->validate([
            'conditions' => ['array'],
            'conditions.*' => [Rule::in(['sellable', 'damaged'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $returns->receive($return->id, array_map('strval', $data['conditions'] ?? []), $data['note'] ?? null);

        return back()->with('success', __('returns::messages.received'));
    }

    public function resolve(ReturnRequest $return, Request $request, ReturnService $returns, OrderReader $orders): RedirectResponse
    {
        Gate::authorize('returns.refund');
        $exchange = $return->resolution === 'exchange';
        $data = $request->validate(['amount' => [$exchange ? 'nullable' : 'required', 'integer', 'min:0'], 'note' => ['nullable', 'string', 'max:255']]);
        $returns->resolve($return->id, $exchange ? null : (int) $data['amount'], $data['note'] ?? null);

        if ($exchange) {
            $number = $orders->find((int) $return->fresh()?->replacement_order_id)?->number;

            return back()->with('success', __('returns::messages.exchanged', ['number' => $number]));
        }

        return back()->with('success', __('returns::messages.resolved'));
    }

    /**
     * Nhãn Admin; tên lý do lấy từ danh mục (cả lý do đã tắt — yêu cầu cũ vẫn hiện đúng tên).
     *
     * @return array<string, mixed>
     */
    private function labels(): array
    {
        return [...(array) __('returns::labels'), 'reason' => array_map(fn ($reason): string => $this->reasons->label($reason->code, App::getLocale()), $this->reasons->all())];
    }
}
