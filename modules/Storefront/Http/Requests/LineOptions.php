<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Validation\ValidationException;

/**
 * Tuỳ chọn dòng giỏ `options.<plugin-id>.<field>`: kiểm tra hình thức (id plugin có dấu chấm nên không dùng rule
 * wildcard của Laravel). Kiểm tra nghiệp vụ do CartLineOption của plugin làm.
 */
final class LineOptions
{
    /**
     * @return array<string, array<string, scalar|null>>
     */
    public static function from(mixed $input): array
    {
        if ($input === null || $input === '') {
            return [];
        }

        $valid = is_array($input) && count($input) <= 10;
        foreach ($valid ? $input : [] as $plugin => $values) {
            $valid = $valid && is_string($plugin) && preg_match('/^[a-z0-9.-]{1,64}$/', $plugin) === 1 && is_array($values) && count($values) <= 20;
            foreach ($valid ? $values : [] as $field => $value) {
                $valid = $valid && is_string($field) && (is_scalar($value) || $value === null) && mb_strlen((string) $value) <= 500;
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages(['options' => __('validation.array', ['attribute' => 'options'])]);
        }

        return $input;
    }
}
