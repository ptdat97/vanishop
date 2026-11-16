<?php

declare(strict_types=1);

namespace Plugin\CostPrice;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Contracts\VariantUsage;
use Modules\Extension\PluginServiceProvider;
use Modules\Pricing\Contracts\BulkPriceMode;
use Modules\Pricing\Contracts\PriceRounding;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\SpreadsheetRows;
use Plugin\CostPrice\Infrastructure\CostMarkupMode;
use Plugin\CostPrice\Infrastructure\CostPrices;
use Plugin\CostPrice\Infrastructure\CostPriceUsage;
use Plugin\CostPrice\Infrastructure\NineThousandRounding;

/**
 * Giá vốn (EP v3 X4 — tham chiếu cho `BulkPriceMode`, `PriceRounding`, hook `vani.pricing.bulk.warnings`; dùng thêm cột +
 * công cụ tải file của X1 trên trang làm việc bảng giá).
 */
final class CostPriceServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.cost-price';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->permissions(['cost-price.manage' => 'Nhập giá vốn']);
        $this->contribute(BulkPriceMode::TAG, CostMarkupMode::class);
        $this->contribute(PriceRounding::TAG, NineThousandRounding::class);
        $this->contribute(VariantUsage::TAG, CostPriceUsage::class);
        $costs = fn (): CostPrices => $this->app->make(CostPrices::class);

        $this->onFilter('vani.pricing.bulk.warnings', function (array $warnings, int $variantId, ?int $listAmount, int $amount) use ($costs): array {
            $cost = $costs()->of([$variantId])[$variantId] ?? null;

            return $cost !== null && $amount < $cost ? [...$warnings, 'Dưới giá vốn'] : $warnings;
        });
        $this->adminColumn('price_list_item', 'cost', 'Giá vốn', resolve: function (array $variantIds) use ($costs): array {
            $money = $this->app->make(MoneyFormatter::class);

            return array_map(fn (int $amount): string => $money->formatAmount($amount, (string) config('vanishop.currency')), $costs()->of($variantIds));
        });
        $this->adminTool('price_list_item', 'import_cost', 'Nhập giá vốn', 'cost-price.manage', handle: function (UploadedFile $file) use ($costs): string {
            $rows = SpreadsheetRows::fromUpload($file);
            $header = array_map(fn (string $cell): string => mb_strtolower(trim($cell)), $rows[0] ?? []);
            $sku = array_search('sku', $header, true);
            $cost = array_search('giá vốn', $header, true);
            if ($sku === false || $cost === false) {
                throw ValidationException::withMessages(['file' => 'File cần cột "SKU" và "Giá vốn" ở dòng đầu.']);
            }
            $values = [];
            foreach (array_slice($rows, 1) as $row) {
                $digits = preg_replace('/\D+/', '', (string) ($row[$cost] ?? ''));
                if (trim((string) ($row[$sku] ?? '')) !== '' && $digits !== '') {
                    $values[trim((string) $row[$sku])] = (int) $digits;
                }
            }
            $found = $this->app->make(VariantDirectory::class)->findBySkus(array_map('strval', array_keys($values)));
            foreach ($found as $code => $variant) {
                $costs()->set($variant->id, $values[$code]);
            }

            return 'Đã nhập giá vốn '.count($found).' SKU'.(count($values) > count($found) ? '; '.(count($values) - count($found)).' SKU không có.' : '.');
        }, help: 'File .xlsx/.csv có cột "SKU" và "Giá vốn".');
    }
}
