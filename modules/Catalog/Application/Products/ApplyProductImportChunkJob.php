<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\BulkRun;
use Throwable;

/**
 * Một lô của lượt nhập file sản phẩm lớn (ProductFile::apply, BulkRun): dòng tiêu đề + dòng của tối đa CHUNK_PRODUCTS sản
 * phẩm. Lập lại kế hoạch lúc chạy (dữ liệu có thể đã đổi), ghi dưới danh tính nhân viên đã bấm. Không thử lại (tries = 1):
 * lô lỗi đếm số dòng vào `failed_rows`, các lô khác vẫn chạy.
 */
final class ApplyProductImportChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @param  list<list<string>>  $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly ContextScope $scope,
    ) {}

    public function handle(CurrentContext $context, ProductFile $file): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
        $batchId = (string) $this->batch()?->id;
        try {
            $result = $context->runAs($this->scope, fn (): array => $file->applyRows($this->rows));
        } catch (Throwable $exception) {
            BulkRun::record($batchId, ['failed_rows' => count($this->rows) - 1], [$exception->getMessage()]);

            throw $exception;
        }
        BulkRun::record($batchId, ['created' => $result['created'], 'updated' => $result['updated']], $result['errors']);
    }
}
