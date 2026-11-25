<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/** Dữ liệu trang bộ sưu tập, dùng giữa Catalog và Storefront. */
interface CollectionPages
{
    /** @return array{id: int, slug: string, slugs: array<string, string>, locales: list<string>, name: string, description: string|null, meta_title: string|null, meta_description: string|null, image_url: string|null, image_srcset: string, status: string}|null */
    public function published(string $slug, string $locale): ?array;

    /** @return array{id: int, slug: string, slugs: array<string, string>, locales: list<string>, name: string, description: string|null, meta_title: string|null, meta_description: string|null, image_url: string|null, image_srcset: string, status: string}|null */
    public function editable(int $id, string $locale): ?array;

    /** @return iterable<array{slugs: array<string, string>, locales: list<string>}> */
    public function sitemapSlugs(int $limit): iterable;
}
