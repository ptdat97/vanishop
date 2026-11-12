<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

/**
 * Cách storefront hiển thị một loại tuỳ chọn (ADR-045): ô màu, nút, hoặc danh sách thả xuống.
 */
enum OptionDisplayType: string
{
    case Swatch = 'swatch';
    case Button = 'button';
    case Dropdown = 'dropdown';
}
