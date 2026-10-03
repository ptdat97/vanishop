<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Payment\Contracts\Data\CallbackOutcome;
use Modules\Payment\Contracts\Data\GatewayCallback;

/**
 * Interface tuỳ chọn cho PaymentGateway (0.3.15): cổng yêu cầu phản hồi IPN theo định dạng riêng cho mọi kết quả
 * (vd. VNPay luôn HTTP 200 + `RspCode`). Không có → Core trả mặc định (400 sai chữ ký, 404 không thấy, 200 + `acknowledgement`).
 */
interface CallbackResponder
{
    /**
     * @param  GatewayCallback|null  $callback  null khi callback không hợp lệ
     * @return array{status: int, body: array<string, mixed>}
     */
    public function callbackResponse(CallbackOutcome $outcome, ?GatewayCallback $callback): array;
}
