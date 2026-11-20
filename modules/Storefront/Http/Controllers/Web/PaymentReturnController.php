<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\GatewayPaymentStatus;
use Modules\Payment\Contracts\Payments;
use Modules\Shared\Support\LocalizedUrls;

/**
 * Return URL chung của cổng thanh toán (`HandlesReturn`): cổng đọc tham số trả về, khách được đưa về trang đơn kèm thông báo.
 * Không ghi nhận thanh toán — trang đơn đọc trạng thái mới nhất (Core chỉ ghi nhận từ callback/IPN đã xác minh).
 */
final class PaymentReturnController
{
    public function __invoke(string $gateway, Request $request, Payments $payments): RedirectResponse
    {
        $result = $payments->resolveReturn($gateway, $request);
        $payment = $result?->paymentPublicId === null ? null : $payments->view($result->paymentPublicId);
        if ($payment === null) {
            return redirect()->to(LocalizedUrls::route('storefront.home'))->withErrors(['business' => __('storefront::messages.payment_return_unknown')]);
        }

        $redirect = redirect()->to(LocalizedUrls::route('storefront.order', $payment->orderPublicId));

        return match ($result->status) {
            GatewayPaymentStatus::Paid, GatewayPaymentStatus::Authorized => $redirect->with('status', __('storefront::messages.payment_return_paid')),
            GatewayPaymentStatus::Failed => $redirect->withErrors(['business' => __('storefront::messages.payment_return_failed')]),
            GatewayPaymentStatus::Pending => $redirect->with('status', __('storefront::messages.payment_return_pending')),
        };
    }
}
