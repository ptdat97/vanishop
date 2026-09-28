<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Contracts\InvalidCallback;
use Modules\Payment\Contracts\PaymentRejected;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * IPN/webhook chung cho mọi cổng: /api/payments/{gateway}/callback. Cổng xác minh chữ ký; Core ghi nhận.
 * Return URL của khách KHÔNG xác nhận thanh toán — chỉ callback đã xác minh mới được.
 */
final class GatewayCallbackController
{
    public function __invoke(string $gateway, Request $request, GatewayRegistry $gateways, PaymentService $payments, CurrentContext $context): JsonResponse
    {
        $implementation = $gateways->get($gateway);
        abort_if($implementation === null || ! $implementation->capabilities()->callbacks, 404);

        try {
            $callback = $implementation->verifyCallback($request);
        } catch (InvalidCallback $exception) {
            Log::channel(config('logging.security_channel', config('logging.default')))->warning('Callback thanh toán không hợp lệ.', [
                'gateway' => $gateway, 'ip' => $request->ip(), 'reason' => $exception->getMessage(),
            ]);

            return response()->json(['error' => ['code' => 'payment.invalid_callback', 'message' => 'Invalid callback.']], 400);
        }

        try {
            $context->runAs(ContextScope::system("payment callback {$gateway}"), fn () => $payments->applyCallback($gateway, $callback));
        } catch (PaymentRejected) {
            return response()->json(['error' => ['code' => 'payment.not_found', 'message' => 'Unknown payment.']], 404);
        }

        return response()->json($callback->acknowledgement);
    }
}
