<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Ảnh trong Thư viện ảnh, kèm URL hiện hành (ảnh gốc + các bản thu nhỏ trong public/cache). Không lưu URL này lâu dài:
 * URL bản thu nhỏ đổi khi đổi định dạng (plugin ImageFormat) hoặc disk — lưu `id` rồi tra lại khi hiển thị.
 */
final readonly class MediaData
{
    /**
     * @param  array<int, string>  $variants  chiều rộng => URL bản thu nhỏ, tăng dần (rỗng = chỉ có ảnh gốc)
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?int $width,
        public ?int $height,
        public string $url,
        public array $variants,
    ) {}

    /**
     * URL bản nhỏ nhất rộng ≥ $width; không có → ảnh gốc.
     */
    public function urlFor(?int $width = null): string
    {
        foreach ($width === null ? [] : $this->variants as $variantWidth => $url) {
            if ($variantWidth >= $width) {
                return $url;
            }
        }

        return $this->url;
    }

    /**
     * Giá trị thuộc tính `srcset` (bản thu nhỏ + ảnh gốc); rỗng khi không có bản thu nhỏ.
     */
    public function srcset(): string
    {
        if ($this->variants === []) {
            return '';
        }
        $sources = array_map(fn (int $width, string $url): string => "{$url} {$width}w", array_keys($this->variants), $this->variants);
        if ($this->width !== null) {
            $sources[] = "{$this->url} {$this->width}w";
        }

        return implode(', ', $sources);
    }
}
