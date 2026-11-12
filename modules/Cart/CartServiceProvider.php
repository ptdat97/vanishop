<?php

declare(strict_types=1);

namespace Modules\Cart;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Cart\Application\CartedVariants;
use Modules\Cart\Application\CartService;
use Modules\Cart\Console\DetectAbandonedCartsCommand;
use Modules\Cart\Console\PruneCartsCommand;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Domain\CartLimits;
use Modules\Catalog\Contracts\VariantUsage;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Support\ModuleServiceProvider;

final class CartServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Cart';
    }

    public function register(): void
    {
        // Ma trận biến thể: SKU đã có ở Cart chỉ ngừng bán, không xoá (variant-matrix §3.2).
        $this->app->make(Extensions::class)->tag([CartedVariants::class], VariantUsage::TAG);
        $this->app->bind(Carts::class, CartService::class);
        $this->app->bind(CartLimits::class, fn (): CartLimits => new CartLimits(
            (int) config('vanishop.cart.max_line_quantity', 20),
            (int) config('vanishop.cart.max_lines', 50),
        ));
    }

    public function boot(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:cart:prune')->dailyAt('03:30')->withoutOverlapping()->onOneServer();
            $schedule->command('vani:cart:detect-abandoned')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([PruneCartsCommand::class, DetectAbandonedCartsCommand::class]);
        }

        $this->bootModuleResources();
    }
}
