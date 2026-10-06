<?php

declare(strict_types=1);

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Application\InventoryReconciler;
use Modules\Inventory\Application\StockVerifier;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Đối soát tồn kho với sổ biến động + hàng giữ (chạy hằng ngày, chỉ báo cáo). Exit 1 khi có chênh lệch.
 * Kết quả được lưu vào bảng đối soát chung (`inventory_reconciliations`) — roadmap Phase 3.
 */
final class VerifyStockCommand extends Command
{
    protected $signature = 'vani:inventory:verify {--repair-reserved : Sửa reserved cho khớp hàng giữ active (ghi biến động reconcile)}';

    protected $description = 'Đối soát tồn kho: reserved ↔ hàng giữ, tồn ↔ sổ biến động, không âm (lưu kết quả vào bảng đối soát)';

    public function handle(StockVerifier $verifier, InventoryReconciler $reconciler, CurrentContext $context): int
    {
        $result = $context->runAs(ContextScope::system('vani:inventory:verify'), fn (): array => $verifier->verify((bool) $this->option('repair-reserved')));

        $reconciler->recordVerifyResult($result);

        $this->info("Đã kiểm {$result['checked']} dòng tồn, {$result['repaired']} dòng sửa reserved.");
        if ($result['issues'] === []) {
            return self::SUCCESS;
        }

        $this->table(['Kho', 'Variant', 'Vấn đề', 'Đúng ra', 'Hiện tại'], array_map(fn (array $issue): array => array_values($issue), array_slice($result['issues'], 0, 200)));
        Log::warning('Đối soát tồn kho có chênh lệch.', ['issues' => count($result['issues']), 'repaired' => $result['repaired'], 'sample' => array_slice($result['issues'], 0, 20)]);

        return $result['repaired'] === count($result['issues']) ? self::SUCCESS : self::FAILURE;
    }
}
