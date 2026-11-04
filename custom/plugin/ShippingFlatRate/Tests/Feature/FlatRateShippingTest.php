<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Testing\Samples;
use Modules\Checkout\Testing\ShippingRateProviderContract;
use Modules\Tenancy\Contracts\Settings;
use Plugin\ShippingFlatRate\Infrastructure\FlatRateShipping;

ShippingRateProviderContract::define('vani.shipping-flat-rate', fn () => app(FlatRateShipping::class));

it('phí và ngưỡng miễn phí theo cấu hình; Admin ghi đè .env', function () {
    config(['vani.shipping-flat-rate.fee' => 30_000, 'vani.shipping-flat-rate.free_over' => null]);
    $options = fn () => app(FlatRateShipping::class)->options(Samples::totalsContext());

    expect($options()[0]->code)->toBe('standard')
        ->and($options()[0]->fee->amount)->toBe(30_000)
        ->and($options()[0]->source)->toBe('vani.shipping-flat-rate');

    T::seed(fn () => app(Settings::class)->set('vani.shipping-flat-rate', 'fee', 25_000));
    expect($options()[0]->fee->amount)->toBe(25_000);

    T::seed(fn () => app(Settings::class)->set('vani.shipping-flat-rate', 'free_over', 1));
    expect($options()[0]->fee->amount)->toBe(0);
});

it('phụ phí theo khối lượng giỏ (1.2.0): mỗi kg vượt mức gồm sẵn, làm tròn lên; chưa cấu hình thì không tính', function () {
    config(['vani.shipping-flat-rate.fee' => 30_000, 'vani.shipping-flat-rate.free_over' => null]);
    $fee = fn () => app(FlatRateShipping::class)->options(Samples::totalsContext())[0]->fee->amount;
    expect(Samples::totalsContext()->weight()->grams)->toBe(1_100)->and($fee())->toBe(30_000);

    T::seed(function () {
        app(Settings::class)->set('vani.shipping-flat-rate', 'included_grams', 500);
        app(Settings::class)->set('vani.shipping-flat-rate', 'fee_per_extra_kg', 5_000);
    });
    expect($fee())->toBe(35_000);

    T::seed(fn () => app(Settings::class)->set('vani.shipping-flat-rate', 'included_grams', 2_000));
    expect($fee())->toBe(30_000);
});
