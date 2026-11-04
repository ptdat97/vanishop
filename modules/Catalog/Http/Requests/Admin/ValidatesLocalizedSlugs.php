<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Closure;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Support\StoreLocale;

/**
 * Slug bản dịch ngôn ngữ không mặc định (ADR-038 §4): tuỳ chọn, dạng kebab-case, không trùng slug riêng cùng ngôn ngữ
 * của đối tượng khác, và không trùng slug gốc của đối tượng khác (URL /en/… tra slug riêng trước, rồi slug gốc).
 */
trait ValidatesLocalizedSlugs
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function localizedSlugRules(string $baseTable, string $translationTable, string $foreignKey, ?int $ignoreId): array
    {
        $rules = [];
        foreach (StoreLocale::supported() as $locale) {
            if ($locale === StoreLocale::default()) {
                continue;
            }
            $rules["translations.{$locale}.slug"] = ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                function (string $attribute, mixed $value, Closure $fail) use ($baseTable, $translationTable, $foreignKey, $ignoreId, $locale): void {
                    $taken = DB::table($translationTable)->where('locale', $locale)->where('slug', $value)->when($ignoreId !== null, fn ($query) => $query->where($foreignKey, '!=', $ignoreId))->exists()
                        || DB::table($baseTable)->where('slug', $value)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists();
                    if ($taken) {
                        $fail(__('catalog::messages.localized_slug_taken'));
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * Bỏ slug của bản dịch ngôn ngữ mặc định (dùng slug gốc), chuỗi rỗng → null.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeLocalizedSlugs(array $translations): array
    {
        foreach ($translations as $locale => $fields) {
            $slug = trim((string) ($fields['slug'] ?? ''));
            $translations[$locale]['slug'] = $locale === StoreLocale::default() || $slug === '' ? null : $slug;
        }

        return $translations;
    }
}
