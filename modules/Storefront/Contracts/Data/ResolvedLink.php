<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts\Data;

/**
 * Kết quả phân giải `LinkTarget`: `exists = false` khi đối tượng đã xoá/ẩn hoặc loại link không còn (plugin tắt) —
 * nơi render bỏ qua link.
 */
final readonly class ResolvedLink
{
    public function __construct(
        public string $url,
        public string $label,
        public bool $exists,
    ) {}

    public static function missing(): self
    {
        return new self('', '', false);
    }
}
