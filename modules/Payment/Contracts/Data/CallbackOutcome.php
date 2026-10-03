<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

/**
 * Kết quả Core xử lý một callback/IPN (0.3.15) — cổng `CallbackResponder` dựa vào đây để trả mã phản hồi riêng.
 */
enum CallbackOutcome: string
{
    /** Đã ghi nhận (thu tiền, giữ tiền, thất bại hoặc chờ). */
    case Applied = 'applied';
    /** Giao dịch đã nhận trước đó, hoặc khoản thanh toán đã thu xong. */
    case Duplicate = 'duplicate';
    /** Số tiền/loại tiền khác khoản thanh toán — đã lưu vết, không ghi nhận. */
    case AmountMismatch = 'amount_mismatch';
    /** Không có khoản thanh toán tương ứng. */
    case NotFound = 'not_found';
    /** Sai chữ ký, thiếu trường (InvalidCallback). */
    case Invalid = 'invalid';
}
