<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Database\Eloquent\Builder;
use Modules\Storefront\Contracts\LinkType;
use Plugin\Cms\Persistence\Content;

/**
 * Link tới trang/bài viết của `vani.cms` theo id cho menu storefront. Chỉ nội dung đang công khai mới phân giải được
 * (nháp, hẹn giờ chưa tới → mục menu bị ẩn).
 */
abstract class ContentLinkType implements LinkType
{
    /** @return Builder<Content> */
    abstract protected function query(): Builder;

    abstract protected function url(string $slug, string $locale): string;

    public function searchable(): bool
    {
        return true;
    }

    public function options(): array
    {
        return [];
    }

    public function search(string $query, string $locale, int $limit = 20): array
    {
        return $this->query()
            ->when(trim($query) !== '', fn (Builder $builder) => $builder->where('title', 'like', '%'.addcslashes(trim($query), '%_\\').'%'))
            ->orderByDesc('updated_at')->limit($limit)->get()
            ->map(fn (Content $content): array => ['value' => (string) $content->id, 'label' => $content->isPublic() ? $content->title : "{$content->title} (chưa công khai)"])
            ->all();
    }

    public function accepts(string $value): bool
    {
        return ctype_digit($value) && (int) $value > 0;
    }

    public function resolve(array $values, string $locale): array
    {
        $ids = array_map('intval', array_filter($values, $this->accepts(...)));

        return $ids === [] ? [] : $this->query()->published()->whereIn('id', $ids)->get()
            ->mapWithKeys(fn (Content $content): array => [(string) $content->id => ['url' => $this->url($content->slug, $locale), 'label' => $content->title]])
            ->all();
    }
}
