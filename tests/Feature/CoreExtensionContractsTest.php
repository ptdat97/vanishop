<?php

use Illuminate\Support\Facades\Mail;
use Modules\Catalog\Application\Search\DatabaseSearchProvider;
use Modules\Catalog\Application\Search\ProductDocumentBuilder;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Testing\SearchProviderContract;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Application\Calculators\GuardCalculator;
use Modules\Checkout\Application\Calculators\SubtotalCalculator;
use Modules\Checkout\Application\Tax\NoTax;
use Modules\Checkout\Testing\TaxCalculatorContract;
use Modules\Checkout\Testing\TotalsCalculatorContract;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Application\OtpSenders\EmailOtpSender;
use Modules\Customer\Testing\OtpSenderContract;
use Modules\Fulfillment\Application\ReservedLocationSourcing;
use Modules\Fulfillment\Testing\SourcingStrategyContract;
use Modules\Inventory\Application\StandardInventoryStrategy;
use Modules\Inventory\Testing\InventoryStrategyContract;
use Modules\Notification\Application\Channels\MailChannel;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Testing\NotificationChannelContract;
use Modules\Pricing\Application\PriceListPriorityStrategy;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Testing\PricingStrategyContract;
use Modules\Promotion\Application\Actions\AmountOffAction;
use Modules\Promotion\Application\Actions\PercentOffAction;
use Modules\Promotion\Testing\PromotionActionContract;
use Modules\Returns\Application\DaysWindowPolicy;
use Modules\Returns\Testing\ReturnPolicyContract;
use Symfony\Component\Mailer\Exception\TransportException;

/*
| Implementation mặc định của Core chạy cùng bộ contract test mà plugin phải chạy (P1-6).
*/

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

TaxCalculatorContract::define('core none', fn () => app(NoTax::class));
TotalsCalculatorContract::define('core subtotal', fn () => app(SubtotalCalculator::class), plugin: false);
TotalsCalculatorContract::define('core guard', fn () => app(GuardCalculator::class), plugin: false);
PromotionActionContract::define('core percent_off', fn () => new PercentOffAction, ['basis_points' => 1000], ['basis_points' => -5]);
PromotionActionContract::define('core amount_off', fn () => new AmountOffAction, ['amount' => 50_000], ['amount' => 'abc']);
ReturnPolicyContract::define('core days_window', fn () => app(DaysWindowPolicy::class));
InventoryStrategyContract::define('core standard', fn () => app(StandardInventoryStrategy::class));
SourcingStrategyContract::define('core reserved_locations', fn () => app(ReservedLocationSourcing::class));

PricingStrategyContract::define('core price_list_priority', fn () => app(PriceListPriorityStrategy::class), function (): array {
    ['s' => $s, 'm' => $m] = C::store();

    return [[$s->id, $m->id], new PricingContext(time())];
});

SearchProviderContract::define('core database', fn () => app(DatabaseSearchProvider::class), function (): array {
    ['brand' => $brand] = C::store();
    $style = T::seed(fn () => Style::query()->where('brand_id', $brand->id)->firstOrFail());
    $document = T::seed(fn () => app(ProductDocumentBuilder::class)->build($style));

    return [$document, new ProductSearchQuery(brandIds: [$brand->id], now: time())];
}, removeHidesFromSearch: false);

NotificationChannelContract::define(
    'core mail',
    fn () => new MailChannel,
    fn () => new OutgoingMessage(1, 'order_placed:1:mail', 'order_placed', new Recipient(email: 'lan@example.com'), 'Tiêu đề', 'Nội dung', [], 1),
    new Recipient(phone: '+84912345678'),
    succeed: fn () => null,
    failTemporarily: fn () => Mail::shouldReceive('raw')->andThrow(new TransportException('SMTP down')),
);

OtpSenderContract::define(
    'core email',
    fn () => new EmailOtpSender,
    succeed: fn () => null,
    fail: fn () => Mail::shouldReceive('raw')->andThrow(new TransportException('SMTP down')),
);
