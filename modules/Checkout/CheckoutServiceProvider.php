<?php

declare(strict_types=1);

namespace Modules\Checkout;

use Illuminate\Support\Facades\Event;
use Modules\Checkout\Application\Calculators\GuardCalculator;
use Modules\Checkout\Application\Calculators\PromotionCalculator;
use Modules\Checkout\Application\Calculators\ShippingCalculator;
use Modules\Checkout\Application\Calculators\SubtotalCalculator;
use Modules\Checkout\Application\Calculators\TaxStage;
use Modules\Checkout\Application\CheckoutService;
use Modules\Checkout\Application\FlatRateShipping;
use Modules\Checkout\Application\Listeners\UndoCancelledOrder;
use Modules\Checkout\Application\ShippingOptions;
use Modules\Checkout\Application\Tax\ConfiguredTaxCalculator;
use Modules\Checkout\Application\TotalsPipeline;
use Modules\Checkout\Application\Validators\CoreCheckoutValidator;
use Modules\Checkout\Application\VnVatInclusiveTax;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Extension\Contracts\Extensions;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;

final class CheckoutServiceProvider extends ModuleServiceProvider
{
    /** @deprecated dùng {@see TaxCalculator::TAG} (public API). */
    public const TAX_TAG = TaxCalculator::TAG;

    protected function moduleName(): string
    {
        return 'Checkout';
    }

    public function register(): void
    {
        $this->app->bind(Checkout::class, CheckoutService::class);

        $this->app->make(Extensions::class)->tag([SubtotalCalculator::class, PromotionCalculator::class, ShippingCalculator::class, TaxStage::class, GuardCalculator::class], TotalsPipeline::TAG);
        $this->app->make(Extensions::class)->tag([CoreCheckoutValidator::class], CheckoutService::VALIDATORS_TAG);

        $this->app->bind(FlatRateShipping::class, fn (): FlatRateShipping => new FlatRateShipping(
            (int) config('vanishop.checkout.shipping.flat_fee', 30_000),
            config('vanishop.checkout.shipping.free_over') === null ? null : (int) config('vanishop.checkout.shipping.free_over'),
        ));
        $this->app->make(Extensions::class)->tag([FlatRateShipping::class], ShippingOptions::TAG);

        $this->app->bind(VnVatInclusiveTax::class, fn (): VnVatInclusiveTax => new VnVatInclusiveTax((int) config('vanishop.tax.vat_rate_bp', 1000)));
        $this->app->make(Extensions::class)->tag([VnVatInclusiveTax::class], self::TAX_TAG);
        $this->app->bind(TaxCalculator::class, fn ($app): TaxCalculator => new ConfiguredTaxCalculator(
            $app->make(Extensions::class), $app->make(Settings::class), (string) config('vanishop.tax.calculator', 'vn_vat_inclusive'),
        ));
    }

    public function boot(): void
    {
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'tax.calculator', 'Cách tính thuế', 'select', (string) config('vanishop.tax.calculator', 'vn_vat_inclusive'),
            [SettingsScope::OWNER, SettingsScope::BRAND, SettingsScope::CHANNEL], optionsFromTag: TaxCalculator::TAG, help: 'TaxCalculator theo kênh/brand của giỏ.',
        ));
        Event::listen(OrderCancelled::class, UndoCancelledOrder::class);

        $this->bootModuleResources();
    }
}
