<?php

declare(strict_types=1);

namespace Plugin\VnPay\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Payment\Contracts\Payments;
use Plugin\VnPay\Infrastructure\VnPayGateway;

/**
 * Return URL: chỉ báo kết quả cho khách rồi chuyển về trang đơn. Không ghi nhận thanh toán — Core chỉ ghi nhận từ IPN
 * đã xác minh (trang đơn đọc trạng thái mới nhất).
 */
final class ReturnController
{
    public function __invoke(Request $request, VnPayGateway $gateway, Payments $payments): RedirectResponse
    {
        $params = array_filter($request->query(), fn ($value, $key): bool => is_string($key) && str_starts_with($key, 'vnp_') && is_scalar($value), ARRAY_FILTER_USE_BOTH);
        $result = $gateway->returnResult($params);
        $payment = $result === 'invalid' ? null : $payments->view((string) ($params['vnp_TxnRef'] ?? ''));

        if ($payment === null || $payment->gatewayCode !== VnPayGateway::CODE) {
            return redirect()->route('storefront.home')->withErrors(['business' => 'Không xác định được giao dịch VNPay.']);
        }

        $redirect = redirect()->route('storefront.order', $payment->orderPublicId);

        return $result === 'paid'
            ? $redirect->with('status', 'Thanh toán VNPay thành công. Cảm ơn bạn!')
            : $redirect->withErrors(['business' => 'Thanh toán VNPay chưa thành công. Bạn có thể thử lại.']);
    }
}
