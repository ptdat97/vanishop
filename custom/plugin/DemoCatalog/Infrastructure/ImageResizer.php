<?php

declare(strict_types=1);

namespace Plugin\DemoCatalog\Infrastructure;

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Thu nhỏ ảnh chụp gốc (~8000px) trước khi đưa vào Core: file tạm JPEG chất lượng 82, cạnh dài ≤ $maxDimension.
 */
final class ImageResizer
{
    private readonly ImageManagerInterface $images;

    public function __construct(private readonly int $maxDimension)
    {
        $this->images = ImageManager::usingDriver(extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class);
    }

    public function toTemporary(string $source, string $directory, string $name): string
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $target = $directory.DIRECTORY_SEPARATOR.$name.'.jpg';
        $this->images->decodePath($source)->scaleDown(width: $this->maxDimension, height: $this->maxDimension)
            ->encode(new JpegEncoder(quality: 82))->save($target);

        return $target;
    }
}
