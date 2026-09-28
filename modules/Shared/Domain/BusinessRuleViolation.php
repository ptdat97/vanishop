<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

use RuntimeException;

/**
 * Lỗi nghiệp vụ công khai: có mã ổn định (vd. "cart.insufficient_stock") để client xử lý.
 * API trả {"error": {"code", "message", "details"}} với HTTP status của lỗi (mặc định 409).
 */
abstract class BusinessRuleViolation extends RuntimeException
{
    abstract public function errorCode(): string;

    public function status(): int
    {
        return 409;
    }

    /**
     * Chi tiết an toàn để trả cho client (không lộ dữ liệu nội bộ như số tồn chính xác).
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return [];
    }
}
