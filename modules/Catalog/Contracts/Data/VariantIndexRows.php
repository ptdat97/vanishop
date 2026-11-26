<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Dòng chỉ mục SKU cho read model của module khác (lưới tồn của Inventory, bảng giá của Pricing — db-refactor G3): module đó
 * giữ bản sao thứ tự hiển thị và văn bản tìm của SKU để lọc/sắp xếp/phân trang bằng SQL mà không join bảng của Catalog.
 * Thứ tự: tên sản phẩm (chữ thường, rỗng → mã) → mã → sản phẩm → vị trí SKU trong sản phẩm (theo trục, `ofProducts`).
 */
final class VariantIndexRows
{
    /**
     * @param  array<int, list<VariantData>>  $byProduct  kết quả `VariantDirectory::ofProducts()`
     * @return list<array{variant_id: int, product_id: int, product_code: string, name_sort: string, variant_rank: int, status: string, tracked: bool, search_text: string, options: list<array{type: string, code: string}>}>
     */
    public static function from(array $byProduct): array
    {
        $rows = [];
        foreach ($byProduct as $productId => $variants) {
            foreach (array_values($variants) as $rank => $variant) {
                $name = mb_strtolower(trim($variant->productName));
                $rows[] = [
                    'variant_id' => $variant->id, 'product_id' => (int) $productId, 'product_code' => $variant->productCode,
                    'name_sort' => mb_substr($name === '' ? mb_strtolower($variant->productCode) : $name, 0, 191),
                    'variant_rank' => $rank, 'status' => $variant->status, 'tracked' => $variant->tracksInventory,
                    'search_text' => mb_substr(mb_strtolower("{$variant->sku} {$variant->productCode} {$variant->productName}"), 0, 512),
                    'options' => array_map(fn (array $option): array => ['type' => mb_substr((string) $option['type'], 0, 32), 'code' => mb_substr((string) $option['code'], 0, 32)], $variant->options),
                ];
            }
        }

        return $rows;
    }
}
