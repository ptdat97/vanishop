<?php

/*
| R28 / ADR-029: Core không chứa implementation mang chính sách kinh doanh hoặc đặc thù thị trường. Cổng thanh toán,
| nguồn phí giao, cách tính thuế nằm ở plugin (COD, chuyển khoản, phí cố định, VAT VN: plugin hệ thống).
| Ngoại lệ trung lập: `NoTax` (dự phòng) và `ConfiguredTaxCalculator` (điểm chọn theo cấu hình).
*/

use Modules\Checkout\Application\Tax\ConfiguredTaxCalculator;
use Modules\Checkout\Application\Tax\NoTax;
use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Payment\Contracts\PaymentGateway;

it('modules/ không có implementation PaymentGateway, ShippingRateProvider, TaxCalculator ngoài danh sách trung lập', function () {
    $neutral = [NoTax::class, ConfiguredTaxCalculator::class];
    $offenders = [];
    $root = realpath(__DIR__.'/../../modules');

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $path = $file->getRealPath();
        if ($file->getExtension() !== 'php' || preg_match('#/(Tests|Testing)/#', $path) === 1) {
            continue;
        }

        $class = 'Modules\\'.str_replace('/', '\\', substr($path, strlen($root) + 1, -4));
        if (! class_exists($class) || in_array($class, $neutral, true)) {
            continue;
        }

        foreach ([PaymentGateway::class, ShippingRateProvider::class, TaxCalculator::class] as $contract) {
            if (is_subclass_of($class, $contract)) {
                $offenders[] = "{$class} implements {$contract}";
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('plugin hệ thống khai báo bundled trong manifest', function () {
    $bundled = [];
    foreach (glob(__DIR__.'/../../custom/plugin/*/vanishop.json') as $file) {
        $manifest = json_decode((string) file_get_contents($file), true);
        if ($manifest['bundled'] ?? false) {
            $bundled[] = $manifest['id'];
        }
    }
    sort($bundled);

    expect($bundled)->toBe(['vani.bank-transfer', 'vani.cod', 'vani.provinces-vn', 'vani.shipping-flat-rate', 'vani.tax-vn-vat']);
});
