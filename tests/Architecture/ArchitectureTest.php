<?php

/*
| Architecture rules — docs/01-principles/architecture-rules.md
*/

$modules = require __DIR__.'/../../config/modules.php';

/** Nợ đã biết của R29 (xử lý ở đợt refactor vòng 0): danh sách chỉ được ngắn lại. */
const RING0_COMMERCE_DEBT = [];

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

// Vòng 0 (microkernel, ADR-029) không biết thương mại: không dùng module nào ngoài vòng 0, kể cả Contracts/Events.
// Test của vòng 0 được dùng helper/fixture thương mại để kiểm tra tích hợp.
$ring0 = ['Shared', 'Tenancy', 'Identity', 'Extension'];
$commerce = array_map(fn (string $module): string => "Modules\\{$module}", array_values(array_diff($modules, $ring0)));
foreach ($ring0 as $module) {
    arch("R29: vòng 0 {$module} không phụ thuộc module thương mại")
        ->expect("Modules\\{$module}")
        ->not->toUse($commerce)
        ->ignoring("Modules\\{$module}\\Tests");
}

// arch() của Pest chỉ xét class: file hàm (helpers.php), file route, file hook không được kiểm. Quét token tên class
// trong mọi file .php của vòng 0 để ranh giới R29 không phụ thuộc loại file.
it('R29: mọi file PHP của vòng 0 (kể cả file hàm, route, hook) không tham chiếu module thương mại', function () use ($ring0, $modules) {
    $commerceModules = array_values(array_diff($modules, $ring0));
    $offenders = [];
    foreach ($ring0 as $module) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path("modules/{$module}"), FilesystemIterator::SKIP_DOTS)) as $file) {
            $path = (string) $file;
            if (! str_ends_with($path, '.php') || str_ends_with($path, '.blade.php') || str_contains($path, '/Tests/')) {
                continue;
            }
            foreach (token_get_all((string) file_get_contents($path)) as $token) {
                if (! is_array($token) || ! in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                    continue;
                }
                $segments = explode('\\', ltrim($token[1], '\\'));
                if ($segments[0] === 'Modules' && in_array($segments[1] ?? '', $commerceModules, true)) {
                    $offenders[] = substr($path, strlen(base_path()) + 1).":{$token[2]} {$token[1]}";
                }
            }
        }
    }

    expect($offenders)->toBe(RING0_COMMERCE_DEBT);
});

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

// Danh sách cấm theo tầng ở trên để lọt class ngoài 4 tầng đó (Support, Facades, Http…). Kiểm theo danh sách cho phép:
// Contracts, Events, Testing, Shared\Domain, PluginServiceProvider và PLUGIN_PUBLIC_SUPPORT (đều có trong snapshot).
it('R5: plugin chỉ tham chiếu class Core thuộc API public (danh sách cho phép)', function () {
    $offenders = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('custom/plugin'), FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = (string) $file;
        if (! str_ends_with($path, '.php') || str_ends_with($path, '.blade.php') || str_contains($path, '/Tests/')) {
            continue;
        }
        foreach (token_get_all((string) file_get_contents($path)) as $token) {
            if (! is_array($token) || ! in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                continue;
            }
            $name = ltrim($token[1], '\\');
            if (! str_starts_with($name, 'Modules\\')
                || preg_match('/^Modules\\\\\w+\\\\(Contracts|Events|Testing)(\\\\|$)/', $name) === 1
                || str_starts_with($name, 'Modules\\Shared\\Domain\\')
                || $name === 'Modules\\Extension\\PluginServiceProvider'
                || in_array($name, PLUGIN_PUBLIC_SUPPORT, true)) {
                continue;
            }
            $offenders[] = substr($path, strlen(base_path()) + 1).":{$token[2]} {$name}";
        }
    }

    expect($offenders)->toBe([]);
});

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

it('ADR-037: class trong modules/ không gọi helper vani_* dành cho plugin/theme (inject contract)', function () {
    $helpers = ['vani_setting', 'vani_money', 'vani_locale', 'vani_customer', 'vani_staff', 'vani_admin_url', 'vani_image', 'vani_media', 'vani_srcset', 'vani_product_ratio', 'vani_asset', 'vani_plugin', 'vani_route', 'vani_link'];
    $offenders = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('modules'), FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = (string) $file;
        if (! str_ends_with($path, '.php') || str_ends_with($path, '.blade.php') || str_contains($path, '/Tests/') || preg_match('#/modules/\w+/helpers\.php$#', $path) === 1) {
            continue;
        }
        $tokens = token_get_all((string) file_get_contents($path));
        foreach ($tokens as $index => $token) {
            if (is_array($token) && $token[0] === T_STRING && in_array(strtolower($token[1]), $helpers, true)) {
                $next = $tokens[$index + 1] ?? null;
                if ($next === '(') {
                    $offenders[] = substr($path, strlen(base_path()) + 1).':'.$token[2].' '.$token[1];
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});
