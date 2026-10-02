<?php

/*
| Architecture rules — docs/01-principles/architecture-rules.md
*/

$modules = require __DIR__.'/../../config/modules.php';

arch('R4: Core không phụ thuộc Plugin')
    ->expect('Modules')
    ->not->toUse('Plugin');

arch('R4: app/ không phụ thuộc Plugin')
    ->expect('App')
    ->not->toUse('Plugin');

foreach ($modules as $module) {
    arch("R8: Domain của {$module} không phụ thuộc framework/hạ tầng")
        ->expect("Modules\\{$module}\\Domain")
        ->not->toUse(['Illuminate\\Database', 'Illuminate\\Support\\Facades', 'Illuminate\\Http', 'Inertia']);

    arch("R9: Controller của {$module} không chứa logic persistence trực tiếp qua DB facade")
        ->expect("Modules\\{$module}\\Http\\Controllers")
        ->not->toUse('Illuminate\\Support\\Facades\\DB');
}

// Plugin chỉ được dùng Contracts/, Events/ và PluginServiceProvider — không dùng tầng nội bộ của module.
// Ngoại lệ có chủ đích: Modules\Shared\Domain\Money là value object của shared kernel, mọi contract
// (PaymentGateway, ShippingCarrier, Promotion…) đều dùng nên plugin phải dùng được.
$pluginForbiddenLayers = [];
foreach ($modules as $module) {
    foreach (['Application', 'Persistence', 'Infrastructure', 'Domain'] as $layer) {
        if ($module === 'Shared' && $layer === 'Domain') {
            continue;
        }
        $pluginForbiddenLayers[] = "Modules\\{$module}\\{$layer}";
    }
}

arch('R5: Plugin chỉ dùng API public của Core')
    ->expect('Plugin')
    ->not->toUse($pluginForbiddenLayers);

// Module downstream (ghép nhiều context) chỉ gọi Contracts/Events của module khác.
foreach ([
    'Cart' => ['Catalog', 'Pricing', 'Inventory'],
    'Promotion' => ['Catalog', 'Pricing', 'Inventory', 'Cart', 'Ordering', 'Checkout'],
    'Ordering' => ['Catalog', 'Pricing', 'Inventory', 'Cart', 'Promotion', 'Checkout'],
    'Customer' => ['Catalog', 'Pricing', 'Inventory', 'Cart', 'Promotion', 'Ordering'],
    'Payment' => ['Catalog', 'Inventory', 'Cart', 'Promotion', 'Ordering', 'Checkout', 'Fulfillment'],
    'Checkout' => ['Catalog', 'Pricing', 'Inventory', 'Cart', 'Promotion', 'Ordering', 'Customer', 'Payment', 'Fulfillment'],
    'Fulfillment' => ['Catalog', 'Inventory', 'Cart', 'Promotion', 'Ordering', 'Checkout', 'Payment'],
    'Returns' => ['Catalog', 'Inventory', 'Cart', 'Promotion', 'Ordering', 'Checkout', 'Payment', 'Fulfillment'],
    'Notification' => ['Catalog', 'Inventory', 'Cart', 'Promotion', 'Ordering', 'Customer', 'Checkout', 'Payment', 'Fulfillment', 'Returns'],
    'Integration' => ['Catalog', 'Pricing', 'Inventory', 'Cart', 'Promotion', 'Ordering', 'Checkout', 'Payment', 'Fulfillment', 'Returns'],
    'Storefront' => ['Catalog', 'Pricing', 'Inventory', 'Cart', 'Promotion', 'Ordering', 'Customer', 'Checkout', 'Payment', 'Fulfillment', 'Returns'],
] as $consumer => $upstreams) {
    $forbidden = [];
    foreach ($upstreams as $upstream) {
        foreach (['Application', 'Persistence', 'Infrastructure', 'Domain'] as $layer) {
            $forbidden[] = "Modules\\{$upstream}\\{$layer}";
        }
    }

    arch("R5: {$consumer} chỉ dùng API public của ".implode(', ', $upstreams))
        ->expect("Modules\\{$consumer}")
        ->not->toUse($forbidden)
        ->ignoring("Modules\\{$consumer}\\Tests");
}

arch('R12: Checkout không gọi HTTP ra ngoài (tích hợp đi qua outbox)')
    ->expect('Modules\\Checkout')
    ->not->toUse('Illuminate\\Support\\Facades\\Http');

arch('Class của module dùng strict types')
    ->expect('Modules')
    ->toUseStrictTypes()
    ->ignoring('Modules\\Extension\\Tests');

arch('Không dùng hàm debug')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

it('Tag extension point chỉ được định nghĩa trong Contracts (plugin tham chiếu được, R5)', function () {
    $offenders = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('modules')));
    foreach ($files as $file) {
        $path = (string) $file;
        if (! str_ends_with($path, '.php') || str_contains($path, '/Contracts/') || str_contains($path, '/Tests/')) {
            continue;
        }
        if (preg_match("/const\\s+[A-Z_]*TAG\\s*=\\s*'vani\\./", (string) file_get_contents($path)) === 1) {
            $offenders[] = str_replace(base_path().'/', '', $path);
        }
    }

    expect($offenders)->toBe([]);
});
