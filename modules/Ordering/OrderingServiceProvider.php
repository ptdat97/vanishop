<?php

declare(strict_types=1);

namespace Modules\Ordering;

use Modules\Ordering\Application\OrderFactory;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Shared\Support\ModuleServiceProvider;

/**
 * Slice 6: tạo đơn (snapshot, số đơn, order_events). State machine, Admin đơn, tra cứu: slice 8.
 */
final class OrderingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Ordering';
    }

    public function register(): void
    {
        $this->app->bind(OrderWriter::class, OrderFactory::class);
    }

    public function boot(): void
    {
        $this->bootModuleResources();
    }
}
