<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Kết quả cổng xử lý lệnh hoàn tiền.
 */
#[PluginApi]
enum RefundOutcome: string
{
    /** Cổng đã hoàn xong. */
    case Succeeded = 'succeeded';
    /** Cổng đã nhận lệnh, tiền về sau (hoàn bất đồng bộ) — Core giữ "đang hoàn" và hỏi lại sau. */
    case Pending = 'pending';
    /** Cổng từ chối / lỗi — Core chuyển khoản hoàn sang chờ nhân viên hoàn tay. */
    case Failed = 'failed';
}
