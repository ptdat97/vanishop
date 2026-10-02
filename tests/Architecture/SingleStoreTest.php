<?php

/*
| Một cửa hàng (ADR-028): brand là thuộc tính catalog, không phải phạm vi dữ liệu.
| `brand_id` chỉ được có ở Catalog (styles) và snapshot trên dòng đơn — docs/12-store/store-and-brand.md §6 bước 14.
*/

it('brand_id chỉ xuất hiện ở migration của Catalog và Ordering (snapshot dòng đơn)', function () {
    $allowed = ['Catalog', 'Ordering'];
    $offenders = [];

    foreach (glob(__DIR__.'/../../modules/*/Persistence/Database/migrations/*.php') as $file) {
        $module = basename(dirname($file, 4));
        if (! in_array($module, $allowed, true) && str_contains((string) file_get_contents($file), "'brand_id'")) {
            $offenders[] = "{$module}/".basename($file);
        }
    }

    expect($offenders)->toBe([]);
});

it('không còn tầng phạm vi brand/kênh trong code', function () {
    $forbidden = ['BelongsToBrand', 'BrandScope', 'X-Vani-Channel', 'plugin_scopes', 'channel_id'];
    $offenders = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../modules', FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $code = (string) file_get_contents($file->getPathname());
        foreach ($forbidden as $needle) {
            if (str_contains($code, $needle)) {
                $offenders[] = str_replace(realpath(__DIR__.'/../..').'/', '', $file->getRealPath()).": {$needle}";
            }
        }
    }

    expect($offenders)->toBe([]);
});
