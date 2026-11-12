<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Returns\Contracts\Returns;
use Modules\Storefront\Http\Requests\ReturnRequestForm;

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

    public function store(ReturnRequestForm $request, string $order, Session $session): RedirectResponse
    {
        // Cùng hợp đồng đầu vào với Storefront API (ADR-044): form Blade được đổi sang dạng chuẩn trong ReturnRequestForm.
        $detail = $this->detail($request, $order, $session);
        $input = $request->toReturnInput();

        $return = $this->returns->request($detail->id, $input['lines'], $input['reason_code'], $input['note'], 'customer', $input['exchanges']);
        if ($input['photos'] !== []) {
            $this->returns->attachPhotos($detail->id, $return->publicId, $input['photos'], 'customer');
        }

        return back()->with('status', __('storefront::messages.return_requested', ['number' => $return->number]));
    }

    public function cancel(Request $request, string $order, string $return, Session $session): RedirectResponse
    {
        $this->returns->cancel($this->detail($request, $order, $session)->id, $return, 'customer');

        return back()->with('status', __('storefront::messages.return_cancelled'));
    }

    /**
     * Khách báo đã gửi hàng về (§3.13-2), kèm mã vận đơn trả nếu có.
     */
    public function shipped(Request $request, string $order, string $return, Session $session): RedirectResponse
    {
        $data = $request->validate(['tracking_number' => ['nullable', 'string', 'max:64']]);
        $this->returns->markShipped($this->detail($request, $order, $session)->id, $return, $data['tracking_number'] ?? null, 'customer');

        return back()->with('status', __('storefront::messages.return_shipped'));
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
