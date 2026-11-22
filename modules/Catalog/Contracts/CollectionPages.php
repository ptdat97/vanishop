<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/** Nội dung của trang bộ sưu tập, dùng giữa Catalog và Storefront. */
interface CollectionPages
{
    public const MEDIA_OWNER = 'catalog.collection_page';

    /** @return array<string, mixed>|null */
    public function published(string $slug, string $locale): ?array;

    /** @return array<string, mixed>|null */
    public function editable(int $id, string $locale): ?array;

    /** @return iterable<array{slugs: array<string, string>, locales: list<string>}> */
    public function sitemapSlugs(int $limit): iterable;

    /**
     * @param  list<array{type: string, config: array<string, mixed>, zone: string}>  $blocks
     * @param  list<int>  $mediaIds
     */
    public function save(int $id, array $blocks, array $mediaIds): void;
}
