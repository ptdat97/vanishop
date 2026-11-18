<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Một lô của việc tồn kho hàng loạt chạy nền (StockBulkRun): `import` = các dòng file đã xem trước (ghi như kiểm kê, dòng
 * đổi sau khi xem trước → xung đột), `bulk` = sửa hàng loạt các biến thể tại một kho. Chạy dưới danh tính nhân viên đã bấm
 * (sổ biến động ghi đúng người). Mỗi biến thể là một biến động riêng; chạy lại lô đã xong không ghi trùng vì `count` so
 * `expected` (import) — `adjust` của bulk thì không chạy lại (tries = 1).
 */
final class ApplyStockChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @param  array<string, mixed>  $payload  import: {file, changes}; bulk: {location_id, variant_ids, action, quantity, reason}
     */
    public function __construct(
        public readonly string $kind,
        public readonly array $payload,
        public readonly ContextScope $scope,
    ) {}

    public function handle(CurrentContext $context, StockCsv $csv, StockBulkEditor $editor, StockQueries $queries): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $result = $context->runAs($this->scope, function () use ($csv, $editor, $queries): array {
            if ($this->kind === 'import') {
                return $csv->applyChanges((string) $this->payload['file'], (array) $this->payload['changes']);
            }
            $location = collect($queries->locations())->firstWhere('id', (int) $this->payload['location_id']);

            return $location === null ? ['skipped' => count((array) $this->payload['variant_ids'])] : $editor->apply(
                $location, array_map('intval', (array) $this->payload['variant_ids']), (string) $this->payload['action'], (int) $this->payload['quantity'], (string) $this->payload['reason'], audit: false,
            );
        });

        $batchId = $this->batch()?->id;
        if ($batchId !== null) {
            StockBulkRun::record($batchId, $result);
        }
    }
}
