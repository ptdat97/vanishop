<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

use InvalidArgumentException;

/**
 * Một kích thước ảnh thu nhỏ (0.3.87): rộng, cao (null = theo tỉ lệ gốc), `crop` = cắt cho đủ khung rộng × cao (không thì
 * thu vừa trong khung, giữ tỉ lệ). Không bao giờ phóng to ảnh gốc. Khoá `key()` nằm trong tên file cache
 * (`-w400`, `-w400h600`, `-w400h600c`) nên đổi kích thước là đổi URL — không có bản cache cũ bị dùng nhầm.
 */
final readonly class ImageSize
{
    public const MIN = 16;

    public const MAX = 4000;

    public function __construct(
        /** Tên, vd. `lookbook.cover` (plugin nên đặt tiền tố riêng). */
        public string $name,
        public string $label,
        public int $width,
        public ?int $height = null,
        public bool $crop = false,
    ) {
        if (preg_match('/^[a-z0-9][a-z0-9_.-]{0,63}$/', $name) !== 1) {
            throw new InvalidArgumentException("Tên kích thước ảnh [{$name}] không hợp lệ.");
        }
        foreach (['width' => $width, 'height' => $height] as $field => $value) {
            if ($value !== null && ($value < self::MIN || $value > self::MAX)) {
                throw new InvalidArgumentException("{$field} của [{$name}] phải trong ".self::MIN.'–'.self::MAX.' px.');
            }
        }
        if ($crop && $height === null) {
            throw new InvalidArgumentException("[{$name}] cắt khung cần chiều cao.");
        }
    }

    public function key(): string
    {
        return 'w'.$this->width.($this->height === null ? '' : 'h'.$this->height.($this->crop ? 'c' : ''));
    }

    /**
     * @return array{name: string, label: string, width: int, height: ?int, crop: bool}
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'label' => $this->label, 'width' => $this->width, 'height' => $this->height, 'crop' => $this->crop];
    }
}
