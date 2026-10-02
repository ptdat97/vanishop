<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

/**
 * Extension point (tag `vani.storefront.enrichers`, ADR-030): plugin bổ sung dữ liệu cho tài nguyên storefront.
 * Chạy ở Presenter nên native storefront và Storefront API nhận cùng dữ liệu. Kết quả chỉ được gắn dưới
 * `extensions.<plugin-id>` của từng phần tử; plugin không sửa dữ liệu của Core hay plugin khác.
 *
 * Nhận cả danh sách một lần (batch, tránh N+1). Lỗi → Core bỏ dữ liệu của plugin đó, trang vẫn chạy.
 * Không gọi mạng đồng bộ; đọc dữ liệu riêng của plugin (bảng plg_*, cache).
 */
interface StorefrontEnricher
{
    public const TAG = 'vani.storefront.enrichers';

    public const RESOURCES = ['product_card', 'product', 'cart', 'order'];

    /**
     * Tài nguyên áp dụng: product_card (phần tử danh sách) | product (PDP) | cart | order.
     */
    public function resource(): string;

    /**
     * @param  list<array<string, mixed>>  $items  dữ liệu đã trình bày của Core (chỉ đọc), mỗi phần tử có `id`
     * @return array<int|string, array<string, mixed>> id → dữ liệu của plugin (bỏ qua phần tử không có gì để thêm)
     */
    public function enrich(array $items, string $locale): array;
}
