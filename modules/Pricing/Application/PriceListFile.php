<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;

/**
 * Xuất/nhập file của một bảng giá (thao tác gọn — đợt 3). File xuất cũng là mẫu nhập: sửa cột "Giá" (và "Giá gạch") rồi
 * nhập lại. Nhập: nhận ra cột theo tiêu đề (không phân biệt hoa thường/dấu): SKU, Giá (giá bán, price), Giá gạch
 * (compare_at); cột khác bị bỏ qua. Ô giá trống = giữ nguyên; 0 hợp lệ (giá 0đ). Dòng lỗi (SKU không có, giá sai, giá gạch
 * không lớn hơn giá) bị bỏ, các dòng khác vẫn được ghi qua PriceListService::setPrices (lịch sử giá, audit).
 */
final class PriceListFile
{
    public const HEADER = ['SKU', 'Sản phẩm', 'Tuỳ chọn', 'Giá niêm yết', 'Giá', 'Giá gạch']; // i18n-ignore: tiêu đề cột file

    private const SKU_HEADERS = ['sku', 'ma sku', 'ma bien the'];

    private const PRICE_HEADERS = ['gia', 'gia ban', 'gia moi', 'gia trong bang', 'price', 'amount'];

    private const COMPARE_HEADERS = ['gia gach', 'gia goc', 'compare_at', 'compare at'];

    /** Số SKU mỗi lần ghi (giới hạn một lô của setPrices). */
    private const CHUNK = 500;

    public function __construct(
        private readonly PriceListWorkspace $workspace,
        private readonly PriceListService $lists,
        private readonly VariantDirectory $variants,
    ) {}

    /**
     * @return list<list<string|int|null>>
     */
    public function export(PriceList $list): array
    {
        $ids = $this->workspace->variantIds($list);
        $found = $this->variants->find($ids);
        $prices = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', $ids)->get()->keyBy('variant_id');
        $base = $this->workspace->baseAmounts($ids);
        $rows = [self::HEADER];
        foreach ($ids as $id) {
            $variant = $found[$id] ?? null;
            if ($variant === null) {
                continue;
            }
            $price = $prices->get($id);
            $rows[] = [$variant->sku, $variant->productName, VariantOptionsText::label($variant->options), $base[$id] ?? null, $price?->amount, $price?->compare_at_amount];
        }

        return $rows;
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array{changed: int, unchanged: int, errors: list<string>}
     */
    public function import(PriceList $list, array $rows): array
    {
        $header = array_map(fn (string $cell): string => trim(Str::lower(Str::ascii($cell))), $rows[0] ?? []);
        $skuColumn = $this->column($header, self::SKU_HEADERS);
        $priceColumn = $this->column($header, self::PRICE_HEADERS);
        $compareColumn = $this->column($header, self::COMPARE_HEADERS);
        if ($skuColumn === null || $priceColumn === null) {
            throw ValidationException::withMessages(['file' => __('pricing::messages.import_header')]);
        }

        $parsed = [];
        $errors = [];
        foreach (array_slice($rows, 1) as $index => $row) {
            $line = $index + 2;
            $sku = trim((string) ($row[$skuColumn] ?? ''));
            $amountCell = trim((string) ($row[$priceColumn] ?? ''));
            if ($sku === '' || $amountCell === '') {
                continue;
            }
            $amount = $this->money($amountCell);
            $compareCell = $compareColumn === null ? '' : trim((string) ($row[$compareColumn] ?? ''));
            $compare = $compareCell === '' ? null : $this->money($compareCell);
            if ($amount === null || ($compareCell !== '' && $compare === null)) {
                $errors[] = __('pricing::messages.import_row_invalid', ['line' => $line, 'sku' => $sku]);

                continue;
            }
            if ($compare !== null && $compare <= $amount) {
                $errors[] = __('pricing::messages.import_row_compare', ['line' => $line, 'sku' => $sku]);

                continue;
            }
            $parsed[$sku] = ['line' => $line, 'amount' => $amount, 'compare_at_amount' => $compare];
        }

        $known = $this->variants->findBySkus(array_map('strval', array_keys($parsed)));
        $updates = [];
        foreach ($parsed as $sku => $row) {
            $variant = $known[(string) $sku] ?? null;
            if ($variant === null) {
                $errors[] = __('pricing::messages.import_row_sku', ['line' => $row['line'], 'sku' => $sku]);

                continue;
            }
            $updates[] = ['variant_id' => $variant->id, 'amount' => $row['amount'], 'compare_at_amount' => $row['compare_at_amount']];
        }

        $changed = 0;
        foreach (array_chunk($updates, self::CHUNK) as $chunk) {
            $changed += $this->lists->setPrices($list, $chunk);
        }

        return ['changed' => $changed, 'unchanged' => count($updates) - $changed, 'errors' => $errors];
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $names
     */
    private function column(array $header, array $names): ?int
    {
        foreach ($header as $index => $cell) {
            if (in_array($cell, $names, true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * "199.000", "199,000 đ", "199000" → 199000. Dấu chấm/phẩy chỉ nhận làm phân cách hàng nghìn (nhóm 3 chữ số) — "199.5",
     * số âm, chữ → null (VND không có phần lẻ).
     */
    private function money(string $cell): ?int
    {
        $value = trim((string) preg_replace('/\s|₫|đ|vnd/iu', '', $cell));
        if (preg_match('/^\d{1,13}$/', $value) !== 1 && preg_match('/^\d{1,3}([.,]\d{3})+$/', $value) !== 1) {
            return null;
        }
        $amount = (int) str_replace(['.', ','], '', $value);

        return $amount <= PriceListService::MAX_AMOUNT ? $amount : null;
    }
}
