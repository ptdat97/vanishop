<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Service contract: tra đối tượng catalog theo id cho link có kiểu (menu, khối giao diện) — theo lô, một truy vấn mỗi
 * loại — và tìm để chọn trong Admin.
 *
 * Loại: `category`, `collection`, `brand`, `product` (style).
 */
interface CatalogLookup
{
    public const KINDS = ['category', 'collection', 'brand', 'product'];

    /**
     * Đối tượng đang hiển thị trên storefront (danh mục và mọi danh mục cha đang hiện; bộ sưu tập/thương hiệu đang hiện;
     * sản phẩm đang bán trong khung thời gian hiển thị). Id không hiển thị/không tồn tại thì không có mặt.
     *
     * @param  'category'|'collection'|'brand'|'product'  $kind
     * @param  list<int>  $ids
     * @return array<int, array{id: int, slug: string, name: string}>
     */
    public function visible(string $kind, array $ids, string $locale): array;

    /**
     * Tìm theo tên/mã (không dấu) cho Admin, gồm cả đối tượng đang ẩn (`visible = false`).
     *
     * @param  'category'|'collection'|'brand'|'product'  $kind
     * @return list<array{id: int, name: string, visible: bool}>
     */
    public function search(string $kind, string $query, string $locale, int $limit = 20): array;
}
