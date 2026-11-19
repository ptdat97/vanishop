<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Storefront\Application\OrderPresenter;
use Modules\Storefront\Http\Requests\TrackOrderRequest;

/**
 * Tra cứu đơn cho khách vãng lai: số đơn + SĐT → thông tin đơn đã che (như GET /orders/track của API).
 */
final class TrackOrderController
{
    public function show(): View
    {
        return view('theme::pages.track', ['order' => null]);
    }

    public function search(TrackOrderRequest $request, CustomerOrders $orders, OrderPresenter $presenter): View
    {
        $data = $request->validated();
        $order = $orders->track(strtoupper(trim($data['number'])), $data['phone']);

        return view('theme::pages.track', [
            'order' => $order === null ? null : $presenter->present($order),
            'notFound' => $order === null,
        ]);
    }
}
