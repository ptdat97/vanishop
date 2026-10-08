<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Returns\Contracts\Returns;

/**
 * Khách gửi / huỷ yêu cầu đổi-trả trên storefront native (order §7, §7.1). Quyền xem đơn như trang đơn: token đơn trong
 * phiên (vừa đặt) hoặc khách đăng nhập là chủ đơn. Lỗi nghiệp vụ (quá hạn, vượt số lượng…) hiện ở đầu trang.
 */
final class ReturnController
{
    public function __construct(
        private readonly CustomerOrders $orders,
        private readonly Returns $returns,
    ) {}

    public function store(Request $request, string $order, Session $session): RedirectResponse
    {
        $detail = $this->detail($request, $order, $session);
        $data = $request->validate([
            'resolution' => ['required', Rule::in(['refund', 'exchange'])],
            'lines' => ['required', 'array', 'max:50'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'lines.*.exchange_variant_id' => ['nullable', 'integer'],
            'reason_code' => ['required', 'string', Rule::in((array) config('vanishop.returns.reasons'))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $lines = [];
        $exchanges = [];
        foreach ($data['lines'] as $lineId => $line) {
            $quantity = (int) ($line['quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }
            $lines[(int) $lineId] = $quantity;
            if ($data['resolution'] === 'exchange') {
                $exchanges[(int) $lineId] = (int) ($line['exchange_variant_id'] ?? 0) ?: throw ValidationException::withMessages(['lines' => __('storefront::messages.return_exchange_missing')]);
            }
        }
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => __('storefront::messages.return_nothing_selected')]);
        }

        $return = $this->returns->request($detail->id, $lines, $data['reason_code'], $data['note'] ?? null, 'customer', $exchanges);

        return back()->with('status', __('storefront::messages.return_requested', ['number' => $return->number]));
    }

    public function cancel(Request $request, string $order, string $return, Session $session): RedirectResponse
    {
        $this->returns->cancel($this->detail($request, $order, $session)->id, $return, 'customer');

        return back()->with('status', __('storefront::messages.return_cancelled'));
    }

    private function detail(Request $request, string $order, Session $session): OrderDetail
    {
        $stored = (array) $session->get("vani.orders.{$order}", []);
        $token = (string) ($stored['token'] ?? '');
        $customer = $request->attributes->get('customer');
        $detail = $this->orders->show($order, $token) ?? ($customer === null ? null : $this->orders->showForCustomer((int) $customer->id, $order));
        abort_if($detail === null, 404);

        return $detail;
    }
}
