<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Database\Eloquent\Builder;
use Plugin\Cms\Persistence\Page;

final class PageLinkType extends ContentLinkType
{
    public function type(): string
    {
        return 'page';
    }

    public function label(): string
    {
        return 'Trang nội dung';
    }

    protected function query(): Builder
    {
        return Page::query();
    }

    protected function url(string $slug): string
    {
        return route('storefront.p.vani-cms.page', $slug);
    }
}
