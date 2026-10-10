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
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Shared\Contracts\PhoneNumberPolicy;

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

    expect($bundled)->toBe(['vani.bank-transfer', 'vani.cod', 'vani.phone-vn', 'vani.provinces-vn', 'vani.shipping-flat-rate', 'vani.tax-vn-vat']);
});

it('modules/ không gắn mã implementation hay id của plugin (Core không biết plugin nào cung cấp gì)', function () {
    // Mã do plugin đóng góp cho extension point chọn theo mã (cổng, thuế, hãng, luật SĐT) + id plugin trong manifest.
    $contracts = [PaymentGateway::class, TaxCalculator::class, ShippingCarrier::class, PhoneNumberPolicy::class];
    $pluginRoot = realpath(__DIR__.'/../../custom/plugin');
    $codes = [];
    foreach (glob($pluginRoot.'/*/vanishop.json') as $file) {
        $codes[(string) json_decode((string) file_get_contents($file), true)['id']] = 'id plugin';
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pluginRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $path = $file->getRealPath();
        if ($file->getExtension() !== 'php' || preg_match('#/(Tests|resources|database)/#', $path) === 1) {
            continue;
        }
        $class = 'Plugin\\'.str_replace('/', '\\', substr($path, strlen($pluginRoot) + 1, -4));
        if (! class_exists($class) || (new ReflectionClass($class))->isAbstract()) {
            continue;
        }
        foreach ($contracts as $contract) {
            if (is_subclass_of($class, $contract)) {
                $codes[(new ReflectionClass($class))->newInstanceWithoutConstructor()->code()] = $class;
            }
        }
    }
    expect($codes)->toHaveKeys(['cod', 'manual_bank_transfer', 'vn_vat_inclusive', 'vani.cod']);

    // Trùng chữ ngẫu nhiên, không phải mã của plugin: hệ size `vn` (cùng us/eu) là từ vựng catalog.
    $coincidences = ['Catalog/Domain/SizeSystem.php' => ['vn']];

    $offenders = [];
    $root = realpath(__DIR__.'/../../modules');
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $path = $file->getRealPath();
        if ($file->getExtension() !== 'php' || preg_match('#/(Tests|Testing)/#', $path) === 1) {
            continue;
        }
        $allowed = $coincidences[substr($path, strlen($root) + 1)] ?? [];
        foreach (token_get_all((string) file_get_contents($path)) as $token) {
            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && isset($codes[$literal = substr($token[1], 1, -1)]) && ! in_array($literal, $allowed, true)) {
                $offenders[] = substr($path, strlen($root) + 1).":{$token[2]} '{$literal}' ({$codes[$literal]})";
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('modules/ không ghi cứng giá trị thị trường của cửa hàng — múi giờ, ngôn ngữ, tiền tệ, định dạng lấy từ config/vanishop.php', function () {
    $market = [];
    foreach (DateTimeZone::listIdentifiers() as $zone) {
        $market[$zone] = 'múi giờ';
    }
    unset($market['UTC']);
    foreach ((array) config('vanishop.locale.supported') as $locale) {
        $market[$locale] = 'ngôn ngữ';
    }
    foreach ((array) config('vanishop.locale.formats') as $format) {
        $market[$format] = 'định dạng ngày';
    }
    $market[(string) config('vanishop.currency')] = 'tiền tệ';
    // Bảng số chữ số thập phân theo ISO 4217 (dữ liệu chuẩn); hằng deprecated giữ cho tương thích tới 1.0.
    $allowed = ['Shared/Domain/Money/Currency.php', 'Extension/Contracts/Data/ReportPeriod.php'];

    $offenders = [];
    $root = realpath(__DIR__.'/../../modules');
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $path = $file->getRealPath();
        $relative = substr($path, strlen($root) + 1);
        if ($file->getExtension() !== 'php' || preg_match('#/(Tests|Testing|lang|database|Database|migrations)/#', $path) === 1 || in_array($relative, $allowed, true)) {
            continue;
        }
        foreach (token_get_all((string) file_get_contents($path)) as $token) {
            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && isset($market[$literal = stripcslashes(substr($token[1], 1, -1))])) {
                $offenders[] = "{$relative}:{$token[2]} '{$literal}' ({$market[$literal]})";
            }
        }
    }

    expect($offenders)->toBe([]);
});
