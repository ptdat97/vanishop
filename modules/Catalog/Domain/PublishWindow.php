<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Khoảng thời gian sản phẩm được hiển thị (null = không giới hạn phía đó).
 */
final readonly class PublishWindow
{
    public function __construct(
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
    ) {
        if ($from !== null && $to !== null && $to <= $from) {
            throw new InvalidArgumentException('Thời điểm kết thúc hiển thị phải sau thời điểm bắt đầu.');
        }
    }

    public function contains(DateTimeInterface $moment): bool
    {
        return ($this->from === null || $moment >= $this->from)
            && ($this->to === null || $moment < $this->to);
    }

    /**
     * Sản phẩm hiển thị trên storefront khi đang active và nằm trong khung giờ.
     */
    public static function isVisible(StyleStatus $status, self $window, DateTimeInterface $now): bool
    {
        return $status === StyleStatus::Active && $window->contains($now);
    }
}
