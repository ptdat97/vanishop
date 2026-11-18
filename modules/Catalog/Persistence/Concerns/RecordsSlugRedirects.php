<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Concerns;

use Modules\Catalog\Persistence\Models\SlugRedirect;

/**
 * Đổi slug gốc → ghi slug cũ vào `slug_redirects` (link cũ chuyển hướng 301). Bản ghi tạo mới hoặc đổi sang một slug đang
 * nằm trong `slug_redirects` thì dòng đó bị xoá: slug thuộc bản ghi đang dùng nó. Model dùng trait khai báo
 * `SLUG_SUBJECT` (`product` | `category` | `brand`).
 */
trait RecordsSlugRedirects
{
    public static function bootRecordsSlugRedirects(): void
    {
        static::created(fn (self $model) => SlugRedirect::query()->where(['subject' => static::SLUG_SUBJECT, 'locale' => '', 'slug' => (string) $model->slug])->delete());

        // `updated` chạy trước khi đồng bộ original: getOriginal('slug') vẫn là slug cũ.
        static::updated(function (self $model): void {
            if (! $model->wasChanged('slug')) {
                return;
            }
            $subject = static::SLUG_SUBJECT;
            SlugRedirect::query()->where(['subject' => $subject, 'locale' => '', 'slug' => (string) $model->slug])->delete();
            $previous = (string) $model->getOriginal('slug');
            if ($previous !== '') {
                SlugRedirect::query()->updateOrCreate(['subject' => $subject, 'locale' => '', 'slug' => $previous], ['subject_id' => (int) $model->getKey()]);
            }
        });
    }
}
