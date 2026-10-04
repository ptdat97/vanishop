<?php

declare(strict_types=1);

namespace Modules\Returns\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Returns\Application\ReturnService;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Persistence\Models\ReturnRequest;

final class ReturnController
{
    public function home(): RedirectResponse
    {
        Gate::authorize('returns.view');

        return redirect()->route('admin.returns.returns.index');
    }

    public function index(Request $request, OrderReader $orders): Response
    {
        Gate::authorize('returns.view');
        $status = (string) $request->query('status', '');

        return Inertia::render('Returns::Returns/Index', [
            'baseUrl' => route('admin.returns.returns.index'),
            'status' => $status,
            'statuses' => array_column(ReturnStatus::cases(), 'value'),
            'returns' => ReturnRequest::query()->withCount('lines')->when($status !== '', fn ($query) => $query->where('status', $status))
                ->orderByDesc('id')->limit(100)->get()->map(fn (ReturnRequest $return): array => [
                    'id' => $return->id, 'number' => $return->number, 'status' => $return->status->value, 'reason_code' => $return->reason_code,
                    'refund_amount' => $return->refund_amount, 'source' => $return->source,
                    'created_at' => $return->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
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
            'storeUrl' => route('admin.returns.returns.store'),
            'orderUrl' => route('admin.orders.orders.show', ['order' => $order]),
            'order' => ['id' => $data->id, 'number' => $data->number],
            'deadline' => $returnable['deadline'],
            'lines' => array_map(fn ($line): array => [
                'id' => $line->id, 'sku' => $line->sku, 'name' => $line->productName, 'color' => $line->colorName, 'size' => $line->sizeCode,
                'ordered' => $line->quantity, 'returnable' => (int) ($returnable['lines'][$line->id] ?? 0),
            ], $orders->lines($order)),
            'reasons' => (array) config('vanishop.returns.reasons'),
        ]);
    }

    public function store(Request $request, ReturnService $returns, OrderReader $orders): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['integer', 'min:0', 'max:1000'],
            'reason_code' => ['required', 'string', Rule::in((array) config('vanishop.returns.reasons'))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        abort_if($orders->find((int) $data['order_id']) === null, 404);

        $lines = [];
        foreach ($data['lines'] as $lineId => $quantity) {
            $lines[(int) $lineId] = (int) $quantity;
        }
        $view = $returns->request((int) $data['order_id'], $lines, $data['reason_code'], $data['note'] ?? null, 'staff');

        return redirect()->route('admin.returns.returns.show', ['return' => $view->id])->with('success', __('returns::messages.created'));
    }

    public function show(ReturnRequest $return, ReturnService $returns, OrderReader $orders): Response
    {
        Gate::authorize('returns.view');
        $canManage = Gate::allows('returns.manage');

        return Inertia::render('Returns::Returns/Show', [
            'baseUrl' => route('admin.returns.returns.index'),
            'orderUrl' => route('admin.orders.orders.show', ['order' => $return->order_id]),
            'orderNumber' => $orders->find($return->order_id)?->number,
            'return' => (array) $returns->view($return),
            'can' => [
                'approve' => $canManage && $return->status->canMoveTo(ReturnStatus::Approved),
                'reject' => $canManage && $return->status->canMoveTo(ReturnStatus::Rejected),
                'in_transit' => $canManage && $return->status->canMoveTo(ReturnStatus::InTransit),
                'receive' => $canManage && $return->status->canMoveTo(ReturnStatus::Received),
                'resolve' => Gate::allows('returns.refund') && $return->status->canMoveTo(ReturnStatus::Resolved),
            ],
        ]);
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

    public function resolve(ReturnRequest $return, Request $request, ReturnService $returns): RedirectResponse
    {
        Gate::authorize('returns.refund');
        $data = $request->validate(['amount' => ['required', 'integer', 'min:0'], 'note' => ['nullable', 'string', 'max:255']]);
        $returns->resolve($return->id, (int) $data['amount'], $data['note'] ?? null);

        return back()->with('success', __('returns::messages.resolved'));
    }
}
