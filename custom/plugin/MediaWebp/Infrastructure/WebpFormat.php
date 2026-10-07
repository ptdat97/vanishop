<?php

declare(strict_types=1);

namespace Plugin\MediaWebp\Infrastructure;

use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\EncoderInterface;
use Modules\Catalog\Contracts\ImageFormat;
use Modules\Tenancy\Contracts\Settings;
use Plugin\MediaWebp\MediaWebpServiceProvider;

/**
 * JPEG (và PNG nếu bật) → WebP. Máy chủ không có encoder WebP (GD thiếu imagewebp, Imagick thiếu delegate) → không nhận
 * ảnh nào, Core giữ định dạng gốc thay vì lỗi khi tạo ảnh.
 */
final class WebpFormat implements ImageFormat
{
    public function __construct(private readonly Settings $settings) {}

    public function code(): string
    {
        return 'webp';
    }

    public function supports(string $sourceMimeType): bool
    {
        $accepted = $sourceMimeType === 'image/jpeg'
            || ($sourceMimeType === 'image/png' && (bool) ($this->settings->get(MediaWebpServiceProvider::ID, 'convert_png') ?? true));

        return $accepted && self::encoderAvailable();
    }

    public function extension(): string
    {
        return 'webp';
    }

    public function encoder(int $quality): EncoderInterface
    {
        $override = $this->settings->get(MediaWebpServiceProvider::ID, 'quality');

        return new WebpEncoder(quality: $override === null ? $quality : max(1, min(100, (int) $override)));
    }

    /**
     * Cùng điều kiện chọn driver với Core (Imagick nếu có, không thì GD).
     */
    public static function encoderAvailable(): bool
    {
        static $available = null;

        return $available ??= extension_loaded('imagick')
            ? \Imagick::queryFormats('WEBP') !== []
            : function_exists('imagewebp');
    }
}
