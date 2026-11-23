<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Payment\Contracts\Payments;
use Modules\Returns\Contracts\Returns;
use Modules\Storefront\Application\OrderPresenter;
use Modules\Storefront\Application\PaymentPresenter;
use Modules\Storefront\Http\Requests\CancelOrderRequest;
use Modules\Storefront\Http\Requests\OrderReceivedRequest;
use Modules\Storefront\Http\Requests\RetryPaymentRequest;
use Modules\Storefront\Http\Requests\ReturnPhotosRequest;
use Modules\Storefront\Http\Requests\ReturnRequestForm;
use Modules\Storefront\Http\Requests\ReturnShippedRequest;
use Modules\Storefront\Http\Requests\TrackOrderRequest;

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

    public function track(TrackOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
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
    public function requestReturn(ReturnRequestForm $request, string $order, Returns $returns): JsonResponse
    {
        // Cùng hợp đồng đầu vào với form đổi trả của Blade (ADR-044).
        $detail = $this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, ''));
        abort_if($detail === null, 404, __('ordering::messages.not_found'));

        $input = $request->toReturnInput();
        $created = $returns->request($detail->id, $input['lines'], $input['reason_code'], $input['note'], 'customer', $input['exchanges']);
        if ($input['photos'] !== []) {
            $returns->attachPhotos($detail->id, $created->publicId, $input['photos'], 'customer');
        }

        return response()->json(['data' => $this->presenter->present($this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, '')))], 201);
    }

    public function cancelReturn(Request $request, string $order, string $return, Returns $returns): JsonResponse
    {
        $detail = $this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, ''));
        abort_if($detail === null, 404, __('ordering::messages.not_found'));
        $returns->cancel($detail->id, $return, 'customer');

        return response()->json(['data' => $this->presenter->present($this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, '')))]);
    }

    /**
     * Khách báo đã gửi hàng về (0.3.74): yêu cầu đã duyệt → đang gửi về, mã vận đơn trả tuỳ chọn.
     */
    /**
     * Gửi thêm ảnh (multipart `photos[]`) cho yêu cầu đổi trả còn mở — L16. Trả đơn (kèm `returns[].attachments`).
     */
    public function addReturnPhotos(ReturnPhotosRequest $request, string $order, string $return, Returns $returns): JsonResponse
    {
        $data = $request->validated();
        $detail = $this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, ''));
        abort_if($detail === null, 404, __('ordering::messages.not_found'));
        $returns->attachPhotos($detail->id, $return, array_values($data['photos']), 'customer');

        return response()->json(['data' => $this->presenter->present($this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, '')))]);
    }

    public function markReturnShipped(ReturnShippedRequest $request, string $order, string $return, Returns $returns): JsonResponse
    {
        $data = $request->validated();
        $detail = $this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, ''));
        abort_if($detail === null, 404, __('ordering::messages.not_found'));
        $returns->markShipped($detail->id, $return, $data['tracking_number'] ?? null, 'customer');

        return response()->json(['data' => $this->presenter->present($this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, '')))]);
    }

    /**
     * Thanh toán lại (0.3.75, §3.11-2): đơn online chưa trả, còn hạn; `gateway` tuỳ chọn để đổi sang cổng online khác.
     * Trả khoản thanh toán (kèm `action` mới của cổng).
     */
    public function retryPayment(RetryPaymentRequest $request, string $order, Payments $payments, PaymentPresenter $paymentPresenter): JsonResponse
    {
        $data = $request->validated();
        $detail = $this->orders->show($order, (string) $request->headers->get(self::TOKEN_HEADER, ''));
        abort_if($detail === null, 404, __('ordering::messages.not_found'));

        return response()->json(['data' => $paymentPresenter->present($payments->retry($detail->id, $data['gateway'] ?? null))]);
    }

    /**
     * Khách xác nhận đã nhận hàng (0.3.78, §3.11-1); `waive_returns` = không đổi trả đơn này.
     */
    public function confirmReceived(OrderReceivedRequest $request, string $order): JsonResponse
    {
        $data = $request->validated();

        return response()->json(['data' => $this->presenter->present(
            $this->orders->confirmReceived($order, (string) $request->headers->get(self::TOKEN_HEADER, ''), (bool) ($data['waive_returns'] ?? false)),
        )]);
    }

    public function cancel(CancelOrderRequest $request, string $order): JsonResponse
    {
        $data = $request->validated();

        return response()->json(['data' => $this->presenter->present(
            $this->orders->cancel($order, (string) $request->headers->get(self::TOKEN_HEADER, ''), $data['reason'] ?? '', $data['reason_code'] ?? null),
        )]);
    }
}
