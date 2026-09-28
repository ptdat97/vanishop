<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

/**
 * Tập dòng đủ điều kiện (khoá dòng). Rỗng = khuyến mãi không áp dụng.
 */
final readonly class Eligibility
{
    /**
     * @param  list<int>  $keys
     */
    public function __construct(public array $keys) {}

    public static function none(): self
    {
        return new self([]);
    }

    public function isEmpty(): bool
    {
        return $this->keys === [];
    }

    public function intersect(self $other): self
    {
        return new self(array_values(array_intersect($this->keys, $other->keys)));
    }
}
