<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Returns\Contracts\Returns;
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

    /**
     * Khách gửi yêu cầu đổi/trả cho dòng đã giao (trong hạn đổi trả).
     */
    public function requestReturn(Request $request, string $order, Returns $returns): JsonResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.order_line_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            // Đổi hàng (0.3.32): variant thay thế cho từng dòng (phải có cho mọi dòng); bỏ trống = trả hàng hoàn tiền.
            'lines.*.exchange_variant_id' => ['nullable', 'integer'],
            'reason_code' => ['required', 'string', Rule::in((array) config('vanishop.returns.reasons'))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $detail = $this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, ''));
        abort_if($detail === null, 404, __('ordering::messages.not_found'));

        $lines = [];
        $exchanges = [];
        foreach ($data['lines'] as $line) {
            $lines[(int) $line['order_line_id']] = ($lines[(int) $line['order_line_id']] ?? 0) + (int) $line['quantity'];
            if (isset($line['exchange_variant_id'])) {
                $exchanges[(int) $line['order_line_id']] = (int) $line['exchange_variant_id'];
            }
        }
        $returns->request($detail->id, $lines, $data['reason_code'], $data['note'] ?? null, 'customer', $exchanges);

        return response()->json(['data' => $this->presenter->present($this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, '')))], 201);
    }

    public function cancelReturn(Request $request, string $order, string $return, Returns $returns): JsonResponse
    {
        $detail = $this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, ''));
        abort_if($detail === null, 404, __('ordering::messages.not_found'));
        $returns->cancel($detail->id, $return, 'customer');

        return response()->json(['data' => $this->presenter->present($this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, '')))]);
    }

    public function cancel(Request $request, string $order): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        return response()->json(['data' => $this->presenter->present(
            $this->orders->cancel($order, (string) $request->headers->get(self::TOKEN_HEADER, ''), $data['reason']),
        )]);
    }
}
