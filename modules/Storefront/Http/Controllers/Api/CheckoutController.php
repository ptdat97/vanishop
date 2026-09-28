<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Checkout\Contracts\Checkout;
use Modules\Storefront\Application\CheckoutPresenter;
use Modules\Storefront\Application\PaymentPresenter;
use Modules\Storefront\Http\Requests\CheckoutRequestForm;

/**
 * Checkout không lưu phiên: client gửi đủ dữ liệu mỗi lần. Đặt hàng bắt buộc Idempotency-Key (ADR-014)
 * và expected_total (tổng khách đã thấy khi quote).
 */
final class CheckoutController
{
    public const IDEMPOTENCY_HEADER = 'Idempotency-Key';

    public function quote(CheckoutRequestForm $request, string $cart, Checkout $checkout, CheckoutPresenter $presenter): JsonResponse
    {
        return response()->json(['data' => $presenter->quote($checkout->quote($request->toCheckoutRequest($cart)))]);
    }

    public function placeOrder(CheckoutRequestForm $request, string $cart, Checkout $checkout, PaymentPresenter $payments): JsonResponse
    {
        $key = (string) $request->headers->get(self::IDEMPOTENCY_HEADER, '');
        abort_if(preg_match('/^[A-Za-z0-9_-]{8,128}$/', $key) !== 1, 400, __('checkout::messages.idempotency_key_required'));

        $result = $checkout->placeOrder($request->toCheckoutRequest($cart), $key);

        $body = $result->payment === null ? $result->body : [...$result->body, 'payment' => $payments->present($result->payment)];

        return response()->json(['data' => $body], $result->status, $result->replayed ? ['Idempotent-Replayed' => 'true'] : []);
    }
}
