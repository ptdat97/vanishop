<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * Ranh giới transaction (stabilization C3): gọi dịch vụ ngoài (cổng thanh toán, hãng vận chuyển, kênh OTP…) KHÔNG được
 * nằm trong transaction DB chưa commit — lời gọi chậm giữ khoá hàng lâu, và transaction rollback không thu hồi được việc
 * đã làm ở phía ngoài. Gọi guard() ngay trước lời gọi ra ngoài.
 *
 * Ngoài production (dev, test, CI) vi phạm ném lỗi để cả bộ test đóng vai máy dò; production chỉ ghi cảnh báo.
 * Transaction bọc test (RefreshDatabase) không tính.
 */
final class ExternalCalls
{
    public static function guard(string $call): void
    {
        if (! self::insideTransaction()) {
            return;
        }

        $message = "Gọi dịch vụ ngoài [{$call}] trong transaction DB chưa commit — chuyển lời gọi ra ngoài transaction hoặc dùng DB::afterCommit().";
        if (app()->isProduction()) {
            Log::warning($message, ['call' => $call]);

            return;
        }

        throw new LogicException($message);
    }

    public static function insideTransaction(): bool
    {
        return app()->bound('db.transactions') && app('db.transactions')->callbackApplicableTransactions()->isNotEmpty();
    }
}
