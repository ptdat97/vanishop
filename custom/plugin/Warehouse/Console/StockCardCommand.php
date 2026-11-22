<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Console;

use Illuminate\Console\Command;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Contracts\LocationRegistry;
use Modules\Inventory\Contracts\StockJournal;

final class StockCardCommand extends Command
{
    protected $signature = 'vani:warehouse:stock-card
        {sku : SKU cần xem}
        {--location= : Mã kho}
        {--limit=100 : Số dòng mới nhất, từ 1 đến 1000}';

    protected $description = 'Xem thẻ kho theo SKU từ sổ biến động';

    public function handle(VariantDirectory $variants, LocationRegistry $locations, StockJournal $journal): int
    {
        $sku = trim((string) $this->argument('sku'));
        $variant = $variants->findBySkus([$sku])[$sku] ?? null;
        if ($variant === null) {
            $this->error('Không tìm thấy SKU.');

            return self::FAILURE;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => StockJournal::MAX_ENTRIES]]);
        if ($limit === false) {
            $this->error('Giới hạn số dòng phải từ 1 đến '.StockJournal::MAX_ENTRIES.'.');

            return self::FAILURE;
        }

        $code = trim((string) $this->option('location'));
        $locationId = null;
        if ($code !== '') {
            foreach ($locations->all() as $location) {
                if ($location->code === $code) {
                    $locationId = $location->id;
                    break;
                }
            }
            if ($locationId === null) {
                $this->error('Không tìm thấy kho.');

                return self::FAILURE;
            }
        }

        $this->info("Thẻ kho {$sku}");
        $this->table(
            ['ID', 'Thời điểm', 'Kho', 'Loại', 'Δ tồn', 'Δ giữ', 'Tồn sau', 'Giữ sau', 'Lý do', 'Tham chiếu', 'Người thực hiện'],
            $journal->movements($variant->id, $limit, $locationId),
        );

        return self::SUCCESS;
    }
}
