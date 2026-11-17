<?php

declare(strict_types=1);

namespace Modules\Catalog\Testing;

use Closure;
use Intervention\Image\Interfaces\EncoderInterface;
use Modules\Catalog\Contracts\ImageFormat;

/**
 * Contract test cho ImageFormat. Kiểm tra: mã và đuôi file hợp lệ; nhận ảnh JPEG phổ biến; trả encoder theo chất lượng.
 */
final class ImageFormatContract
{
    /**
     * @param  Closure(): ImageFormat  $format
     */
    public static function define(string $label, Closure $format): void
    {
        describe("ImageFormat contract: {$label}", function () use ($format): void {
            it('mã, đuôi file hợp lệ; nhận JPEG; encoder theo chất lượng', function () use ($format): void {
                expect($format()->code())->toMatch('/^[a-z][a-z0-9_-]*$/')
                    ->and($format()->extension())->toMatch('/^[a-z0-9]{2,5}$/')
                    ->and($format()->supports('image/jpeg'))->toBeTrue()
                    ->and($format()->supports('application/pdf'))->toBeFalse()
                    ->and($format()->encoder(80))->toBeInstanceOf(EncoderInterface::class);
            });
        });
    }
}
