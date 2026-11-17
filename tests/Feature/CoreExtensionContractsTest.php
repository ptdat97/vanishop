<?php

use App\Observability\MailAlertChannel;
use Illuminate\Support\Facades\Mail;
use Modules\Cart\Application\CartedVariants;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Catalog\Application\Search\DatabaseSearchProvider;
use Modules\Catalog\Application\Search\ProductDocumentBuilder;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Testing\SearchProviderContract;
use Modules\Catalog\Testing\VariantUsageContract;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Application\Calculators\GuardCalculator;
use Modules\Checkout\Application\Calculators\SubtotalCalculator;
use Modules\Checkout\Application\Tax\NoTax;
use Modules\Checkout\Application\Validators\CoreCheckoutValidator;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\Totals;
use Modules\Checkout\Testing\CheckoutValidatorContract;
use Modules\Checkout\Testing\Samples;
use Modules\Checkout\Testing\TaxCalculatorContract;
use Modules\Checkout\Testing\TotalsCalculatorContract;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Application\OtpSenders\EmailOtpSender;
use Modules\Customer\Testing\OtpSenderContract;
use Modules\Fulfillment\Application\ReservedLocationSourcing;
use Modules\Fulfillment\Testing\SourcingStrategyContract;
use Modules\Inventory\Application\StandardInventoryStrategy;
use Modules\Inventory\Application\StockedVariants;
use Modules\Inventory\Testing\InventoryStrategyContract;
use Modules\Notification\Application\Channels\MailChannel;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Testing\NotificationChannelContract;
use Modules\Ordering\Application\OrderedVariants;
use Modules\Ordering\Testing\OrderStatusActionContract;
use Modules\Ordering\Tests\Fixtures\ContractOrders;
use Modules\Ordering\Tests\Fixtures\RequeryPaymentStatusAction;
use Modules\Pricing\Application\PricedVariants;
use Modules\Pricing\Application\PriceListPriorityStrategy;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Testing\PricingStrategyContract;
use Modules\Promotion\Application\Actions\AmountOffAction;
use Modules\Promotion\Application\Actions\PercentOffAction;
use Modules\Promotion\Testing\PromotionActionContract;
use Modules\Returns\Application\DaysWindowPolicy;
use Modules\Returns\Application\ReturnedVariants;
use Modules\Returns\Testing\ReturnPolicyContract;
use Modules\Shared\Application\Phone\InternationalPhonePolicy;
use Modules\Shared\Contracts\Data\Alert;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Testing\AlertChannelContract;
use Modules\Shared\Testing\PhoneNumberPolicyContract;
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

// Củng cố kiến trúc K3 (P4): các tag còn thiếu bộ contract test — implementation của Core chạy cùng bộ test với plugin.
$variantIds = function (): array {
    ['s' => $s, 'm' => $m] = C::store();

    return [$s->id, $m->id];
};
foreach ([
    'ordered' => OrderedVariants::class,
    'stocked' => StockedVariants::class,
    'carted' => CartedVariants::class,
    'returned' => ReturnedVariants::class,
    'priced' => PricedVariants::class,
] as $name => $class) {
    VariantUsageContract::define("core {$name}", fn () => app($class), $variantIds);
}
PhoneNumberPolicyContract::define('core international', fn () => app(InternationalPhonePolicy::class), ['+84912345678', '0084 912 345 678']);
AlertChannelContract::define('core mail', function () {
    Mail::fake();

    return app(MailAlertChannel::class);
}, fn () => new Alert('queue.stuck', 'critical', 'open', 'Hàng đợi kẹt', 'Có job chờ quá 10 phút.', new DateTimeImmutable));

require_once __DIR__.'/../../modules/Ordering/Tests/Fixtures/ContractOrders.php';
require_once __DIR__.'/../../modules/Ordering/Tests/Fixtures/RequeryPaymentStatusAction.php';
OrderStatusActionContract::define('fixture requery (cổng thanh toán)', fn () => app(RequeryPaymentStatusAction::class),
    fn () => ContractOrders::data(ContractOrders::seed(overrides: ['payment_status' => 'unpaid', 'payment_method' => 'manual_bank_transfer'])));
$coreCheckoutRequest = fn (array $contact = []) => new CheckoutRequest(
    new CartKey('01JCONTRACTTESTCART0000001', 'token'),
    [...C::orderPayload()['contact'], ...$contact], C::orderPayload()['shipping_address'], 'standard', 'cod', [], null, 429_000,
);
CheckoutValidatorContract::define('core', fn () => app(CoreCheckoutValidator::class),
    valid: fn () => $coreCheckoutRequest(),
    invalid: fn () => $coreCheckoutRequest(['full_name' => '', 'phone' => 'abc']),
    totals: function () {
        $totals = Samples::totals();

        return new Totals($totals->currencyCode, $totals->lines, [], $totals->subtotal, $totals->discount,
            new ShippingOption('standard', 'Giao tiêu chuẩn', Money::vnd(30_000)),
            $totals->tax, $totals->grandTotal, []);
    });
