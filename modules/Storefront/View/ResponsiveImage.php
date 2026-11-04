<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

/**
 * Thuộc tính `sizes` cho ảnh trong lưới/khối (reference-parity §3.16-5): tính từ bố cục, không đọc kích thước file.
 * `srcset` lấy từ dữ liệu ảnh (các bề rộng bản thu nhỏ Core tạo, vd. `image_srcset` của thẻ sản phẩm).
 */
final class ResponsiveImage
{
    /**
     * @param  int|null  $containerWidth  bề rộng tối đa (px) của vùng chứa lưới; null = tràn màn hình
     * @param  int  $columns  số cột từ `$breakpoint` trở lên
     * @param  int  $mobileColumns  số cột dưới `$breakpoint`
     */
    public static function sizes(?int $containerWidth = null, int $columns = 1, int $mobileColumns = 1, int $breakpoint = 768): string
    {
        $columns = max(1, $columns);
        $mobile = (int) ceil(100 / max(1, $mobileColumns)).'vw';
        $desktop = "(min-width: {$breakpoint}px) ".(int) ceil(100 / $columns).'vw';

        if ($containerWidth === null || $containerWidth <= $breakpoint) {
            return "{$desktop}, {$mobile}";
        }

        // Màn hình rộng hơn vùng chứa: mỗi cột cố định theo px.
        return "(min-width: {$containerWidth}px) ".(int) ceil($containerWidth / $columns)."px, {$desktop}, {$mobile}";
    }
}
