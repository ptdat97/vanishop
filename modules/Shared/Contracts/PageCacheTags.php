<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

/**
 * Bộ gom tag của request (ADR-051): presenter, read model, khối Builder, slot ghi lại **dữ liệu nào đã dùng** để dựng trang
 * công khai. Cache trang lưu HTML kèm các tag này; dữ liệu đổi thì {@see PageCacheInvalidator} xoá theo tag.
 *
 * Quy ước tên tag: `product:{id}`, `category:{id}`, `collection:{id}`, `brand:{id}`, `catalog:listing` (danh sách không cố
 * định theo danh mục — mới nhất, tìm kiếm, tất cả sản phẩm), `catalog:taxonomy` (cây danh mục, danh sách thương hiệu, thuộc
 * tính lọc), `builder:{vùng}`, `menu:{vị trí}`, `settings:{namespace}`. Plugin dùng tiền tố mã plugin (`vani.cms:page:12`).
 *
 * Khối hoặc slot có nội dung phụ thuộc request (vị trí khách, A/B, giờ thực, cookie) gọi `uncacheable()`: trang đó không
 * được lưu. Ngoài request trang công khai (Admin, job, API), ghi tag không có tác dụng.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface PageCacheTags
{
    /** Danh sách không cố định theo danh mục/bộ sưu tập/thương hiệu (mới nhất, tìm kiếm, trang có quá nhiều sản phẩm). */
    public const CATALOG_LISTING = 'catalog:listing';

    /** Cây danh mục, danh sách thương hiệu, bộ sưu tập, thuộc tính lọc. */
    public const CATALOG_TAXONOMY = 'catalog:taxonomy';

    /** Có ở mọi trang đã cache: xoá tag này là xoá toàn bộ cache trang. */
    public const EVERYTHING = '*';

    public function add(string ...$tags): void;

    /**
     * Thêm `product:{id}` cho từng sản phẩm.
     *
     * @param  iterable<int>  $productIds
     */
    public function products(iterable $productIds): void;

    /** Trang hiện tại không được lưu vào cache (lý do ngắn, hiện ở header gỡ lỗi). */
    public function uncacheable(string $reason): void;
}
