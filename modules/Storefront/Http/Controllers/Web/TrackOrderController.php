<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Storefront\Application\OrderPresenter;

/**
 * Tra cứu đơn cho khách vãng lai: số đơn + SĐT → thông tin đơn đã che (như GET /orders/track của API).
 */
final class TrackOrderController
{
    public function show(): View
    {
        return view('theme::pages.track', ['order' => null]);
    }

    public function search(Request $request, CustomerOrders $orders, OrderPresenter $presenter): View
    {
        $data = $request->validate(['number' => ['required', 'string', 'max:32'], 'phone' => ['required', 'string', 'max:20']]);
        $order = $orders->track(strtoupper(trim($data['number'])), $data['phone']);

        return view('theme::pages.track', [
            'order' => $order === null ? null : $presenter->present($order),
            'notFound' => $order === null,
        ]);
    }
}
