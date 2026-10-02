<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

/**
 * Kết quả kiểm tra sức khoẻ của plugin (cấu hình thiếu, token hết hạn, quota…).
 */
final readonly class HealthStatus
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const ERROR = 'error';

    public function __construct(
        public string $status,
        public string $message = '',
    ) {
        if (! in_array($status, [self::OK, self::WARNING, self::ERROR], true)) {
            throw new \InvalidArgumentException("Trạng thái sức khoẻ [{$status}] không hợp lệ.");
        }
    }

    public static function ok(string $message = ''): self
    {
        return new self(self::OK, $message);
    }

    public static function warning(string $message): self
    {
        return new self(self::WARNING, $message);
    }

    public static function error(string $message): self
    {
        return new self(self::ERROR, $message);
    }
}
