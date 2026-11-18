<?php

declare(strict_types=1);

namespace Modules\Storefront\View\Composers;

use Illuminate\View\View;
use Modules\Storefront\View\ResponsiveImage;

/**
 * `theme::partials.image`: `$sizes` dựng từ `$layout = [vùng chứa px|null, cột desktop, cột mobile]` (mặc định một cột) khi
 * ảnh có `srcset` — partial chỉ in ra.
 */
final class ImageComposer
{
    public function compose(View $view): void
    {
        $data = $view->getData();
        [$container, $columns, $mobileColumns] = array_values((array) ($data['layout'] ?? [])) + [null, 1, 1];
        $view->with('sizes', ($data['srcset'] ?? null) ? ResponsiveImage::sizes($container === null ? null : (int) $container, (int) $columns, (int) $mobileColumns) : null);
    }
}
