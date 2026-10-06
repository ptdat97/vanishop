<?php

declare(strict_types=1);

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Modules\Inventory\Application\InventoryReconciler;

/**
 * Đối soát tồn kho với nguồn ngoài (roadmap Phase 3): snapshot (location, sku/variant, on_hand, version)
 * so với `on_hand` của VaniShop. Chỉ áp số lên tồn VaniShop khi location do chính nguồn đó quản lý
 * (`stock_authority === source`); chênh lệch khác chỉ được ghi lại để xử lý tay.
 */
final class ReconcileInventoryCommand extends Command
{
    protected $signature = 'vani:inventory:reconcile
        {--source= : Mã nguồn ngoài (khớp locations.stock_authority), hiện trên màn hình đối soát}
        {--file= : Đường dẫn file JSON snapshot}
        {--json= : Chuỗi JSON snapshot (thay cho --file, dùng cho script/test)}
        {--dry-run : Chỉ ghi bảng đối soát, không áp số lên tồn VaniShop}';

    protected $description = 'Đối soát tồn kho với nguồn ngoài theo snapshot';

    public function handle(InventoryReconciler $reconciler): int
    {
        $source = (string) $this->option('source');
        if ($source === '' || $source === 'vanishop') {
            $this->error('--source là bắt buộc và phải khác "vanishop" (mã authority ngoài).');
            $this->line('Ví dụ: php artisan vani:inventory:reconcile --source=client_erp --file=snapshot.json');

            return self::FAILURE;
        }

        $json = $this->snapshotJson();
        if ($json === null) {
            $this->error('Cần một trong hai: --file <đường dẫn> hoặc --json "<chuỗi JSON>".');
            $this->line('Định dạng: [{"location":"WH-HN","sku":"LM-DR01-M","on_hand":8,"version":41}]');

            return self::FAILURE;
        }
        $rows = json_decode($json, true);
        if (! is_array($rows)) {
            $this->error('JSON snapshot không hợp lệ.');

            return self::FAILURE;
        }

        $result = $reconciler->reconcileExternal($source, $rows, apply: ! $this->option('dry-run'));

        $this->info("Đối soát #{$result['id']}: {$result['checked']} dòng kiểm, {$result['discrepancies']} chênh lệch, {$result['repaired']} đã áp (bỏ qua {$result['skipped']} dòng).");

        return $result['discrepancies'] === $result['repaired'] ? self::SUCCESS : self::FAILURE;
    }

    private function snapshotJson(): ?string
    {
        $file = (string) $this->option('file');
        $inline = (string) $this->option('json');
        if ($file !== '') {
            $path = base_path($file);
            $content = is_file($path) ? file_get_contents($path) : false;

            return $content === false ? null : $content;
        }

        return $inline === '' ? null : $inline;
    }
}
