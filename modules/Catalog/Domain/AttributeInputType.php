<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

enum AttributeInputType: string
{
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Text = 'text';
    case Boolean = 'boolean';

    /**
     * Kiểu chọn từ danh sách giá trị định sẵn.
     */
    public function hasOptions(): bool
    {
        return $this === self::Select || $this === self::Multiselect;
    }
}
