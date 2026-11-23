<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Catalog\Contracts\VariantDirectory;

/**
 * Đọc dòng "SKU" / "SKU<tab/,/;>số lượng" từ chữ dán, máy quét hoặc file (phiếu nhập A6, kiểm kê A7): thiếu số lượng = 1, SKU
 * lặp lại cộng dồn (quét từng món), không phân biệt hoa thường; dòng tiêu đề bỏ qua.
 */
final class SkuQuantityRows
{
    public function __construct(private readonly VariantDirectory $variants) {}

    /**
     * Chữ dán/quét → dòng (mỗi dòng một SKU, "SKU 5" = 5).
     *
     * @return list<list<string>>
     */
    public static function fromText(string $text): array
    {
        return array_map(fn (string $line): array => preg_split('/\s*[\t,;]\s*|\s{2,}|\s+(?=\d+$)/', trim($line)) ?: [], preg_split('/\r\n|\n|\r/', $text) ?: []);
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array{lines: array<int, int>, errors: list<string>}
     */
    public function parse(array $rows): array
    {
        $parsed = [];
        $errors = [];
        foreach ($rows as $index => $row) {
            $sku = trim((string) ($row[0] ?? ''));
            $quantity = trim((string) ($row[1] ?? ''));
            if ($sku === '') {
                continue;
            }
            if ($index === 0 && mb_strtolower($sku) === 'sku' && $quantity !== '' && ! ctype_digit($quantity)) {
                continue;
            }
            if ($quantity !== '' && ! ctype_digit($quantity)) {
                $errors[] = __('inventory::messages.receipt_quantity_invalid', ['line' => $index + 1, 'sku' => $sku]);

                continue;
            }
            $parsed[] = ['line' => $index + 1, 'sku' => $sku, 'quantity' => $quantity === '' ? 1 : (int) $quantity];
        }
        $found = [];
        foreach ($this->variants->findBySkus(array_values(array_unique(array_column($parsed, 'sku')))) as $variant) {
            $found[mb_strtoupper($variant->sku)] = $variant;
        }
        $lines = [];
        foreach ($parsed as $row) {
            $variant = $found[mb_strtoupper($row['sku'])] ?? null;
            if ($variant === null) {
                $errors[] = __('inventory::messages.receipt_sku_unknown', ['line' => $row['line'], 'sku' => $row['sku']]);

                continue;
            }
            $lines[$variant->id] = ($lines[$variant->id] ?? 0) + $row['quantity'];
        }

        return ['lines' => $lines, 'errors' => $errors];
    }
}
