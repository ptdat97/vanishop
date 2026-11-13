<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Modules\Shared\Support\StoreLocale;

/**
 * Validator có tên trường dễ đọc cho khoá lồng nhau: không có tên riêng trong `validation.attributes` thì ghép tên của phần
 * cuối với ngữ cảnh — `translations.en.name` → "Tên (English)", `cells.2.sku` → "SKU (dòng 3)", `images.0` → "ảnh (dòng 1)".
 * Khoá có tên riêng (truyền khi validate hoặc trong lang) giữ nguyên.
 */
final class LocalizedValidator extends Validator
{
    /**
     * @param  string  $attribute
     */
    public function getDisplayableAttribute($attribute): string
    {
        $display = (string) parent::getDisplayableAttribute($attribute);
        if (! str_contains($attribute, '.') || ($display !== $attribute && $display !== str_replace('_', ' ', Str::snake($attribute)))) {
            return $display;
        }

        $segments = explode('.', $attribute);
        $context = [];
        while ($segments !== [] && ctype_digit((string) end($segments))) {
            array_unshift($context, $this->rowLabel((int) array_pop($segments)));
        }
        $last = (string) array_pop($segments);
        $locales = array_column(StoreLocale::options(), 'label', 'code');
        foreach ($segments as $segment) {
            if (ctype_digit($segment)) {
                $context[] = $this->rowLabel((int) $segment);
            } elseif (isset($locales[$segment])) {
                $context[] = $locales[$segment];
            }
        }

        $key = "validation.attributes.{$last}";
        $name = $this->translator->get($key);
        $name = is_string($name) && $name !== $key ? $name : str_replace('_', ' ', $last);

        return $context === [] ? $name : $name.' ('.implode(', ', $context).')';
    }

    private function rowLabel(int $index): string
    {
        return (string) $this->translator->get('shared::errors.validation_row', ['number' => $index + 1]);
    }
}
