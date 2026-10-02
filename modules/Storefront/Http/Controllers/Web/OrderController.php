<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Storefront\Application\OrderPresenter;

/**
 * Trang cảm ơn / chi tiết đơn vừa đặt: token xem đơn giữ trong phiên (thay header X-Vani-Order-Token của API).
 */
final class OrderController
{
    public function show(Request $request, string $order, Session $session, CustomerOrders $orders, OrderPresenter $presenter): View
    {
        $stored = (array) $session->get("vani.orders.{$order}", []);
        $customer = $request->attributes->get('customer');
        $detail = $orders->show($order, (string) ($stored['token'] ?? ''))
            ?? ($customer === null ? null : $orders->showForCustomer((int) $customer->id, $order));
        abort_if($detail === null, 404);

        return view('theme::pages.order', [
            'order' => $presenter->present($detail),
            'payment' => $stored['payment'] ?? null,
        ]);
    }
}
