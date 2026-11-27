<?php

declare(strict_types=1);

namespace Modules\Checkout;

use Illuminate\Support\Facades\Event;
use Modules\Checkout\Application\Calculators\GuardCalculator;
use Modules\Checkout\Application\Calculators\PromotionCalculator;
use Modules\Checkout\Application\Calculators\ShippingCalculator;
use Modules\Checkout\Application\Calculators\SubtotalCalculator;
use Modules\Checkout\Application\Calculators\TaxStage;
use Modules\Checkout\Application\CheckoutFlows;
use Modules\Checkout\Application\CheckoutService;
use Modules\Checkout\Application\Listeners\UndoCancelledOrder;
use Modules\Checkout\Application\ReplacementOrderService;
use Modules\Checkout\Application\Tax\ConfiguredTaxCalculator;
use Modules\Checkout\Application\Tax\NoTax;
use Modules\Checkout\Application\TotalsPipeline;
use Modules\Checkout\Application\Validators\CoreCheckoutValidator;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\CheckoutValidator;
use Modules\Checkout\Contracts\ReplacementOrders;
use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Checkout\Contracts\TotalsCalculator;
use Modules\Checkout\Contracts\VoucherPreview;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Flows;
use Modules\Extension\Contracts\Requirement;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

final class CheckoutServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Checkout';
    }

    public function register(): void
    {
        $this->app->bind(Checkout::class, CheckoutService::class);
        $this->app->bind(VoucherPreview::class, CheckoutService::class);
        $this->app->bind(ReplacementOrders::class, ReplacementOrderService::class);

        $this->app->make(Extensions::class)->tag([SubtotalCalculator::class, PromotionCalculator::class, ShippingCalculator::class, TaxStage::class, GuardCalculator::class], TotalsCalculator::TAG);
        $this->app->make(Extensions::class)->tag([CoreCheckoutValidator::class], CheckoutValidator::TAG);

        // Phí giao và thuế theo thị trường là plugin hệ thống (vani.shipping-flat-rate, vani.tax-vn-vat — ADR-029);
        // Core chỉ giữ `none` (không thuế) làm dự phòng trung lập.
        $this->app->make(Extensions::class)->requires(ShippingRateProvider::TAG, Requirement::AtLeastOne, 'checkout::texts.checkout_service_provider.shipping_fee');
        $this->app->make(Extensions::class)->kindContract('shipping_rate', ShippingRateProvider::TAG);
        $this->app->make(Extensions::class)->tag([NoTax::class], TaxCalculator::TAG);
        $this->app->make(Extensions::class)->requires(TaxCalculator::TAG, Requirement::ExactlyOne, 'checkout::texts.checkout_service_provider.tax_calculation');
        $this->app->make(Extensions::class)->kindContract('tax', TaxCalculator::TAG);
        $this->app->make(Extensions::class)->doctorCheck('totals_calculator_priority', function (): ?string {
            $misplaced = $this->app->make(TotalsPipeline::class)->misplacedPluginCalculators();

            return $misplaced === [] ? null : __('checkout::texts.checkout_service_provider.misplaced_calculators', ['calculators' => implode(', ', $misplaced)]);
        });
        $this->app->bind(TaxCalculator::class, fn ($app): TaxCalculator => new ConfiguredTaxCalculator(
            $app->make(Extensions::class), $app->make(Settings::class), (string) config('vanishop.tax.calculator', NoTax::CODE),
        ));
    }

    public function boot(): void
    {
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', 'tax.calculator', 'checkout::texts.checkout_service_provider.tax_calculation', 'select', (string) config('vanishop.tax.calculator', NoTax::CODE),
            optionsFromTag: TaxCalculator::TAG, help: 'checkout::texts.checkout_service_provider.store_s_taxcalculator_if_chosen',
            group: SettingGroup::Sales,
        ));
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', CheckoutService::GUEST_CHECKOUT, 'checkout::texts.checkout_service_provider.allow_guest_checkout', 'bool', true,
            help: 'checkout::texts.checkout_service_provider.off_customers_must_sign_place',
            group: SettingGroup::Sales,
        ));
        Event::listen(OrderCancelled::class, UndoCancelledOrder::class);
        // `vani:flows`: bước dựng lúc xem (plugin đang bật).
        $flows = $this->app->make(Flows::class);
        $flows->describe(CheckoutFlows::QUOTE, 'checkout::texts.flows.quote', fn (): array => $this->app->make(CheckoutFlows::class)->quote());
        $flows->describe(CheckoutFlows::PLACE_ORDER, 'checkout::texts.flows.place_order', fn (): array => $this->app->make(CheckoutFlows::class)->placeOrder());

        // Tạo đơn trong Admin (stabilization A8).
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
    }
}
