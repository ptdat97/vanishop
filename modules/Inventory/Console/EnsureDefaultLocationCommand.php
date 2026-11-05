<?php

declare(strict_types=1);

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Modules\Inventory\Application\LocationService;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Cửa hàng chưa có kho → tạo "Kho chính" (giao đơn online, VaniShop quản lý tồn). `vani:install` gọi lệnh này; chạy lại an toàn.
 */
final class EnsureDefaultLocationCommand extends Command
{
    protected $signature = 'vani:inventory:ensure-location';

    protected $description = 'Tạo "Kho chính" khi cửa hàng chưa có kho nào (chạy lại an toàn)';

    public function handle(LocationService $locations, CurrentContext $context): int
    {
        $created = $context->runAs(ContextScope::system('vani:inventory:ensure-location'), fn () => $locations->ensureDefault());

        $this->info($created === null ? 'Đã có kho, không tạo thêm.' : "Đã tạo kho {$created->code} ({$created->name}).");

        return self::SUCCESS;
    }
}
