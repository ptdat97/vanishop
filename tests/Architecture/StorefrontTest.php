<?php

/*
| Native storefront (storefront §6, R10): controller chỉ dùng tầng Application/Contracts, không chạm Persistence/Domain
| của module nào; view của theme không truy vấn DB.
*/

$modules = require __DIR__.'/../../config/modules.php';

arch('Controller storefront không dùng Persistence/Domain của module')
    ->expect('Modules\\Storefront\\Http\\Controllers')
    ->not->toUse(array_merge(...array_map(fn (string $module): array => ["Modules\\{$module}\\Persistence", "Modules\\{$module}\\Domain"], $modules)));

it('view của theme không truy vấn DB', function () {
    $offenders = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../custom/theme', FilesystemIterator::SKIP_DOTS)) as $file) {
        if (str_ends_with($file->getFilename(), '.blade.php') && preg_match('/DB::|::query\(|->get\(\)|Persistence\\\\/', (string) file_get_contents($file->getPathname())) === 1) {
            $offenders[] = $file->getFilename();
        }
    }

    expect($offenders)->toBe([]);
});

it('mọi slot storefront đã khai báo đều được theme vani-base render (khai báo = public API)', function () {
    $views = '';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../custom/theme/vani-base/views', FilesystemIterator::SKIP_DOTS)) as $file) {
        $views .= file_get_contents($file->getPathname());
    }

    $slots = array_keys(array_filter(require __DIR__.'/../../modules/Storefront/hooks.php', fn (array $hook): bool => $hook['type'] === 'slot'));
    $missing = array_values(array_filter($slots, fn (string $slot): bool => ! str_contains($views, "name=\"{$slot}\"")));

    expect($missing)->toBe([]);
});
