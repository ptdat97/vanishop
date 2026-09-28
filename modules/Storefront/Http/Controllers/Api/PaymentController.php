<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Contracts\Payments;
use Modules\Storefront\Application\PaymentPresenter;

/**
 * Trang kết quả sau khi khách quay về từ cổng: chỉ ĐỌC trạng thái (ghi nhận tiền chỉ qua callback đã xác minh).
 */
final class PaymentController
{
    public function show(string $payment, Payments $payments, PaymentPresenter $presenter): JsonResponse
    {
        $view = $payments->view($payment);
        abort_if($view === null, 404, __('payment::messages.not_found'));

        return response()->json(['data' => $presenter->present($view)]);
    }
}
