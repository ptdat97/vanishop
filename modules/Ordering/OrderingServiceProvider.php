<?php

declare(strict_types=1);

namespace Modules\Ordering;

use Modules\Ordering\Application\EloquentOrderReader;
use Modules\Ordering\Application\OrderFactory;
use Modules\Ordering\Application\OrderTransitionService;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Shared\Support\ModuleServiceProvider;

/**
 * Slice 6: tạo đơn (snapshot, số đơn, order_events). Slice 7: state machine + OrderTransitions (Payment cần).
 * Admin đơn, tra cứu, huỷ bởi khách/CSKH: slice 8.
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
        $this->app->bind(OrderReader::class, EloquentOrderReader::class);
        $this->app->bind(OrderTransitions::class, OrderTransitionService::class);
    }

    public function boot(): void
    {
        $this->bootModuleResources();
    }
}
