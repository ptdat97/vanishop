<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Concerns;

use Modules\Catalog\Persistence\Models\SlugRedirect;

/**
 * Bản dịch có slug riêng (ADR-038 §4) đổi slug → ghi slug cũ của ngôn ngữ đó vào `slug_redirects` (backlog L44); slug
 * được bản dịch khác lấy lại thì dòng bị xoá. Model dùng trait khai báo `SLUG_SUBJECT` và `SLUG_OWNER_KEY` (cột id bản ghi gốc).
 */
trait RecordsTranslatedSlugRedirects
{
    public static function bootRecordsTranslatedSlugRedirects(): void
    {
        static::saved(function (self $translation): void {
            $slug = (string) $translation->getAttribute('slug');
            $locale = (string) $translation->getAttribute('locale');
            if ($slug !== '' && ($translation->wasRecentlyCreated || $translation->wasChanged('slug'))) {
                SlugRedirect::query()->where(['subject' => static::SLUG_SUBJECT, 'locale' => $locale, 'slug' => $slug])->delete();
            }
            $previous = (string) $translation->getOriginal('slug');
            if (! $translation->wasRecentlyCreated && $translation->wasChanged('slug') && $previous !== '' && $previous !== $slug) {
                SlugRedirect::query()->updateOrCreate(
                    ['subject' => static::SLUG_SUBJECT, 'locale' => $locale, 'slug' => $previous],
                    ['subject_id' => (int) $translation->getAttribute(static::SLUG_OWNER_KEY)],
                );
            }
        });
    }
}
