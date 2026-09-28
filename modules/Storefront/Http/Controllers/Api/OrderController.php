<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Storefront\Application\OrderPresenter;

/**
 * Đơn của khách vãng lai: tra cứu bằng số đơn + SĐT (thông tin bị che), xem/huỷ bằng access token trả lúc đặt.
 */
final class OrderController
{
    public const TOKEN_HEADER = 'X-Vani-Order-Token';

    public function __construct(
        private readonly CustomerOrders $orders,
        private readonly OrderPresenter $presenter,
    ) {}

    public function track(Request $request): JsonResponse
    {
        $data = $request->validate(['number' => ['required', 'string', 'max:32'], 'phone' => ['required', 'string', 'max:20']]);
        $order = $this->orders->track($data['number'], $data['phone']);
        abort_if($order === null, 404, __('ordering::messages.not_found'));

        return response()->json(['data' => $this->presenter->present($order)]);
    }

    public function show(Request $request, string $order): JsonResponse
    {
        $detail = $this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, ''));
        abort_if($detail === null, 404, __('ordering::messages.not_found'));

        return response()->json(['data' => $this->presenter->present($detail)]);
    }

    public function cancel(Request $request, string $order): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        return response()->json(['data' => $this->presenter->present(
            $this->orders->cancel($order, (string) $request->headers->get(self::TOKEN_HEADER, ''), $data['reason']),
        )]);
    }
}
