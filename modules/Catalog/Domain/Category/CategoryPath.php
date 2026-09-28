<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Category;

use InvalidArgumentException;

/**
 * Materialized path của danh mục: "/12/57/" = gốc 12 → 57. Bất biến, thuần PHP.
 */
final readonly class CategoryPath
{
    public const MAX_DEPTH = 5;

    /**
     * @param  list<int>  $ids  id từ gốc tới chính nó
     */
    private function __construct(public array $ids) {}

    public static function root(int $id): self
    {
        return new self([$id]);
    }

    public static function fromString(string $path): self
    {
        if (preg_match('#^/(\d+/)+$#', $path) !== 1) {
            throw new InvalidArgumentException("Path danh mục [{$path}] không hợp lệ.");
        }

        return new self(array_map('intval', explode('/', trim($path, '/'))));
    }

    public function child(int $id): self
    {
        if ($this->depth() >= self::MAX_DEPTH) {
            throw new CategoryTooDeep(self::MAX_DEPTH);
        }

        return new self([...$this->ids, $id]);
    }

    public function id(): int
    {
        return $this->ids[array_key_last($this->ids)];
    }

    /**
     * Độ sâu: gốc = 1.
     */
    public function depth(): int
    {
        return count($this->ids);
    }

    /**
     * $this nằm trong cây con của $ancestor (kể cả chính nó).
     */
    public function isWithin(self $ancestor): bool
    {
        return array_slice($this->ids, 0, $ancestor->depth()) === $ancestor->ids;
    }

    /**
     * Path mới khi cây con gốc $from được chuyển thành $to.
     */
    public function rebase(self $from, self $to): self
    {
        if (! $this->isWithin($from)) {
            throw new InvalidArgumentException('Path không thuộc cây con cần chuyển.');
        }

        $ids = [...$to->ids, ...array_slice($this->ids, $from->depth())];
        if (count($ids) > self::MAX_DEPTH) {
            throw new CategoryTooDeep(self::MAX_DEPTH);
        }

        return new self($ids);
    }

    public function toString(): string
    {
        return '/'.implode('/', $this->ids).'/';
    }
}
