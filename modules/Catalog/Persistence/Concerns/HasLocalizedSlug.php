<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Shared\Support\StoreLocale;

/**
 * Slug theo ngôn ngữ (ADR-038 §4): cột `slug` của bảng gốc là slug ngôn ngữ mặc định; bản dịch ngôn ngữ khác có thể có
 * slug riêng. Dùng cùng `HasTranslations` (cần quan hệ `translations` đã nạp để tránh truy vấn từng bản ghi).
 */
trait HasLocalizedSlug
{
    /** Slug ở `$locale`: slug của bản dịch nếu có, ngược lại slug gốc. */
    public function slugFor(string $locale): string
    {
        if ($locale === StoreLocale::default()) {
            return (string) $this->slug;
        }
        $translated = $this->translations->firstWhere('locale', $locale)?->getAttribute('slug');

        return is_string($translated) && $translated !== '' ? $translated : (string) $this->slug;
    }

    /**
     * @return array<string, string> mã ngôn ngữ => slug
     */
    public function slugs(): array
    {
        $slugs = [];
        foreach (StoreLocale::supported() as $locale) {
            $slugs[$locale] = $this->slugFor($locale);
        }

        return $slugs;
    }

    /**
     * Tìm theo slug ở `$locale`: ngôn ngữ mặc định chỉ khớp slug gốc; ngôn ngữ khác khớp slug của bản dịch hoặc slug
     * gốc (link cũ/không có slug riêng — nơi gọi chuyển hướng 301 sang slug riêng nếu có).
     *
     * @param  Builder<static>  $query
     */
    public function scopeWhereLocalizedSlug(Builder $query, string $slug, string $locale): void
    {
        if ($locale === StoreLocale::default()) {
            $query->where($this->qualifyColumn('slug'), $slug);

            return;
        }

        $query->where(fn (Builder $where) => $where
            ->where($this->qualifyColumn('slug'), $slug)
            ->orWhereHas('translations', fn (Builder $translation) => $translation->where('locale', $locale)->where('slug', $slug)));
    }
}
