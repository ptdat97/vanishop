<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts\Data;

use DateTimeImmutable;

/**
 * Một lần báo cảnh báo vận hành tới kênh (AlertChannel). Nội dung không chứa dữ liệu cá nhân của khách.
 */
final readonly class Alert
{
    public const CRITICAL = 'critical';

    public const HIGH = 'high';

    public const NORMAL = 'normal';

    /** Mới phát sinh. */
    public const FIRING = 'firing';

    /** Vẫn còn, nhắc lại theo chu kỳ của mức độ. */
    public const REMINDER = 'reminder';

    /** Đã hết. */
    public const RESOLVED = 'resolved';

    public function __construct(
        public string $key,
        public string $severity,
        public string $state,
        public string $title,
        public string $detail,
        public DateTimeImmutable $since,
    ) {}

    /** Dòng tiêu đề ngắn cho email/tin nhắn, vd. "[KHẨN] Message order.* vào dead". */
    public function subject(): string
    {
        $level = ['critical' => 'KHẨN', 'high' => 'CAO', 'normal' => 'THƯỜNG'][$this->severity] ?? strtoupper($this->severity);
        $prefix = match ($this->state) {
            self::RESOLVED => "[ĐÃ ỔN] [{$level}]",
            self::REMINDER => "[CÒN] [{$level}]",
            default => "[{$level}]",
        };

        return "{$prefix} {$this->title}";
    }
}
