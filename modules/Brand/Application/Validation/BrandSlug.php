<?php

declare(strict_types=1);

namespace Modules\Brand\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Shared\Support\AdminPath;

/**
 * Slug brand là đường dẫn storefront trên domain chung (vani.vn/{slug}) — ADR-019.
 * Không được trùng đường dẫn dành riêng (api, tai-khoan, đường dẫn Admin…).
 */
final class BrandSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) !== 1 || strlen($value) > 64) {
            $fail('brand::validation.slug_format')->translate();

            return;
        }

        if (in_array($value, AdminPath::reservedPaths(), true)) {
            $fail('brand::validation.slug_reserved')->translate();
        }
    }
}
