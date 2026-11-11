<?php

declare(strict_types=1);

namespace Modules\Pricing\Console;

use Illuminate\Console\Command;
use Modules\Pricing\Application\ListPriceSync;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Tính lại giá niêm yết thấp nhất của mọi sản phẩm cho bộ lọc/sắp xếp theo giá. Chạy một lần sau khi nâng cấp lên 0.4.4,
 * và hằng ngày theo lịch để tự sửa lệch. Chạy lại an toàn.
 */
final class SyncListPricesCommand extends Command
{
    protected $signature = 'vani:pricing:sync-list-prices';

    protected $description = 'Ghi giá niêm yết thấp nhất của sản phẩm sang Catalog (lọc/sắp xếp theo giá; chạy lại an toàn)';

    public function handle(ListPriceSync $sync, CurrentContext $context): int
    {
        $count = $context->runAs(ContextScope::system('vani:pricing:sync-list-prices'), fn (): int => $sync->all());

        $this->info("Đã tính lại giá niêm yết cho sản phẩm của {$count} biến thể có giá.");

        return self::SUCCESS;
    }
}
