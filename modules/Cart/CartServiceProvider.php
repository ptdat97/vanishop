<?php

declare(strict_types=1);

namespace Modules\Cart;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Cart\Application\CartedVariants;
use Modules\Cart\Application\CartService;
use Modules\Cart\Application\CartViewBuilder;
use Modules\Cart\Console\DetectAbandonedCartsCommand;
use Modules\Cart\Console\PruneCartsCommand;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartSettings;
use Modules\Cart\Domain\CartLimits;
use Modules\Catalog\Contracts\VariantUsage;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

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
        // Ghi nhớ giỏ đã dựng trong request (trang giỏ, mã giảm giá, báo giá cùng đọc một giỏ).
        $this->app->scoped(CartViewBuilder::class);
        // Giới hạn đọc lúc dùng (Admin → Cài đặt → Bán hàng & thuế; mặc định .env).
        $this->app->bind(CartLimits::class, fn ($app): CartLimits => new CartLimits(
            (int) $app->make(Settings::class)->get('core', CartSettings::MAX_LINE_QUANTITY),
            (int) $app->make(Settings::class)->get('core', CartSettings::MAX_LINES),
        ));
    }

    public function boot(): void
    {
        $settings = $this->app->make(Settings::class);
        $text = fn (string $key): string => "cart::texts.settings.{$key}";
        foreach ([
            new SettingDefinition('core', CartSettings::MAX_LINE_QUANTITY, $text('max_line_quantity'), 'int', (int) config('vanishop.cart.max_line_quantity', 20), help: $text('max_line_quantity_help'), rules: ['min:1', 'max:999'], group: SettingGroup::Sales),
            new SettingDefinition('core', CartSettings::MAX_LINES, $text('max_lines'), 'int', (int) config('vanishop.cart.max_lines', 50), help: $text('max_lines_help'), rules: ['min:1', 'max:200'], group: SettingGroup::Sales),
            new SettingDefinition('core', CartSettings::ABANDONED_AFTER_MINUTES, $text('abandoned_after_minutes'), 'int', (int) config('vanishop.cart.abandoned_after_minutes', 60), help: $text('abandoned_after_minutes_help'), rules: ['min:15', 'max:10080'], group: SettingGroup::Sales),
            new SettingDefinition('core', CartSettings::TTL_DAYS, $text('ttl_days'), 'int', (int) config('vanishop.cart.ttl_days', 30), help: $text('ttl_days_help'), rules: ['min:1', 'max:365'], group: SettingGroup::Sales),
        ] as $definition) {
            $settings->define($definition);
        }

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
