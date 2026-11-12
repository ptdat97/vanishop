<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;

/**
 * Extension point: nhà cung cấp tìm kiếm sản phẩm (tag vani.search.providers).
 * Core có "database" (truy vấn SQL — provider dự án đang dùng; plugin Meilisearch đã gỡ 2026-11-12). Chỉ mục ngoài, nếu cần sau
 * này, là plugin; provider có chỉ mục cần cấu hình implement thêm ConfigurableSearchIndex.
 *
 * @see docs/04-extension/extension-point-catalog.md
 */
interface SearchProvider
{
    /** Tag extension point: plugin đóng góp qua `contribute(SearchProvider::TAG, …)`. */
    public const TAG = 'vani.search.providers';

    public function code(): string;

    /**
     * Thêm/cập nhật tài liệu. Idempotent.
     */
    public function index(ProductDocument $document): void;

    /**
     * Gỡ style khỏi chỉ mục. Idempotent.
     */
    public function remove(int $styleId): void;

    public function search(ProductSearchQuery $query): ProductSearchResult;
}
