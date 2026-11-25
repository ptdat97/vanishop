<?php

declare(strict_types=1);

namespace Modules\Ordering\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Modules\Ordering\Application\AdminQueries;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Application\OrderStatusChanger;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderActionRejected;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Admin → Đơn hàng: đổi trạng thái đơn (ô chọn, hàng loạt, hoàn tất sớm). Quy tắc nằm ở OrderStatusChanger; controller
 * chỉ validate đầu vào, kiểm quyền theo kế hoạch đổi và trả thông báo.
 */
final class OrderStatusController
{
    public function __construct(private readonly AdminQueries $queries) {}

    /**
     * Đổi trạng thái từ ô chọn. Ngoài trình tự cần quyền `orders.override_status` và ghi chú.
     */
    public function changeStatus(Order $order, Request $request, OrderStatusChanger $changer): RedirectResponse
    {
        $data = $request->validate($this->statusChangeRules());
        $to = OrderStatus::from($data['status']);
        $plan = $changer->plan($order, $to);
        if ($plan['mode'] === null) {
            throw OrderActionRejected::statusChangeBlocked((string) $plan['blocked']);
        }
        Gate::authorize((string) $plan['permission']);
        $mode = $changer->change($order, $to, $data['reason'] ?? null, $data['reason_code'] ?? null);

        return back()->with('success', __($mode === 'forced' ? 'ordering::messages.status_changed_forced' : 'ordering::messages.status_changed'));
    }

    /**
     * Đổi trạng thái hàng loạt: đơn bị chặn, thiếu quyền hoặc lỗi nghiệp vụ được bỏ qua — không hỏng cả lô.
     */
    public function bulkStatus(Request $request, OrderStatusChanger $changer): RedirectResponse
    {
        Gate::authorize('orders.manage');
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'min:1'],
            ...$this->statusChangeRules(),
        ]);
        $to = OrderStatus::from($data['status']);
        $note = $data['reason'] ?? null;
        $reasonCode = $data['reason_code'] ?? null;
        $changer->resolveReason($to, $note, $reasonCode); // mã lý do sai → báo lỗi trước khi đổi đơn nào
        $eligible = $this->queries->ordersByIds($data['ids'])->filter(function (Order $order) use ($changer, $to, $note): bool {
            $plan = $changer->plan($order, $to);
            if ($plan['mode'] === null || ! Gate::allows((string) $plan['permission'])) {
                return false;
            }
            $changer->assertNote($plan, $note);

            return true;
        });
        $changed = 0;
        foreach ($eligible as $order) {
            try {
                $changer->change($order, $to, $note, $reasonCode);
                $changed++;
            } catch (BusinessRuleViolation) {
                // Đơn đổi trạng thái giữa chừng: bỏ qua.
            }
        }

        return back()->with('success', __('ordering::messages.bulk_status_changed', ['count' => $changed, 'skipped' => count(array_unique($data['ids'])) - $changed]));
    }

    /** @return array<string, list<Enum|string>> */
    private function statusChangeRules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
            ...$this->statusReasonRules(),
        ];
    }

    /** @return array<string, list<string>> */
    private function statusReasonRules(): array
    {
        return [
            'reason' => ['required_without:reason_code', 'nullable', 'string', 'max:255'],
            'reason_code' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Hoàn tất đơn sớm (đã giao hết, không có đổi/trả đang mở) — 0.3.98.
     */
    public function complete(Order $order, Request $request, OrderCommands $commands, OrderStatusChanger $changer): RedirectResponse
    {
        Gate::authorize('orders.manage');
        $data = $request->validate($this->statusReasonRules());
        $reasonCode = $data['reason_code'] ?? null;
        $reason = $changer->resolveReason(OrderStatus::Completed, $data['reason'] ?? null, $reasonCode);
        $commands->complete($order->id, $reason, $reasonCode);

        return back()->with('success', __('ordering::messages.completed'));
    }
}
