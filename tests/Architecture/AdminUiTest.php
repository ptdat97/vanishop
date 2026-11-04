<?php

/*
| ADR-039: Admin dùng Reka UI qua bộ component `@admin/Components`. Trang của module/plugin chỉ dùng bộ component đó
| (đổi thư viện bên dưới không làm vỡ trang plugin), và không ghi cứng ngôn ngữ/ký hiệu tiền (dùng MoneyText/useMoney).
*/

/**
 * @return list<string> đường dẫn tương đối của mọi file .vue/.ts thuộc Admin
 */
function adminUiFiles(): array
{
    $root = realpath(__DIR__.'/../..');
    $files = [];
    foreach ([...glob("{$root}/modules/*/resources/js", GLOB_ONLYDIR), ...glob("{$root}/custom/plugin/*/Resources/js", GLOB_ONLYDIR), "{$root}/resources/js/admin"] as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (in_array($file->getExtension(), ['vue', 'ts'], true)) {
                $files[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }
    }

    return $files;
}

it('chỉ bộ component @admin/Components được import reka-ui', function () {
    $offenders = array_values(array_filter(
        adminUiFiles(),
        fn (string $file): bool => ! str_starts_with($file, 'resources/js/admin/Components/') && str_contains((string) file_get_contents(base_path($file)), "from 'reka-ui'"),
    ));

    expect($offenders)->toBe([]);
});

it('giao diện Admin không ghi cứng ngôn ngữ hay ký hiệu tiền tệ (chỉ còn các trang chưa chuyển)', function () {
    // Trang chưa chuyển sang MoneyText/useMoney — chuyển xong trang nào thì xoá khỏi danh sách.
    $pending = [
        'modules/Customer/resources/js/Pages/Customers/Show.vue',
        'modules/Extension/resources/js/Pages/Plugins/Show.vue',
        'modules/Fulfillment/resources/js/Pages/Shipments/Index.vue',
        'modules/Identity/resources/js/Pages/Staff/Index.vue',
        'modules/Payment/resources/js/Pages/Payments/Index.vue',
        'modules/Pricing/resources/js/Pages/PriceLists/Prices.vue',
        'modules/Promotion/resources/js/Pages/Campaigns/Form.vue',
        'modules/Promotion/resources/js/Pages/Promotions/Form.vue',
        'modules/Promotion/resources/js/Pages/Promotions/Index.vue',
        'modules/Returns/resources/js/Pages/Returns/Create.vue',
        'modules/Returns/resources/js/Pages/Returns/Index.vue',
        'modules/Returns/resources/js/Pages/Returns/Show.vue',
        'resources/js/admin/Components/Insights/types.ts',
    ];

    $offenders = array_values(array_filter(adminUiFiles(), fn (string $file): bool => preg_match('/vi-VN|₫/u', (string) file_get_contents(base_path($file))) === 1));
    sort($offenders);

    expect(array_values(array_diff($offenders, $pending)))->toBe([], 'File mới ghi cứng vi-VN/₫: dùng MoneyText hoặc useMoney()')
        ->and(array_values(array_diff($pending, $offenders)))->toBe([], 'File đã chuyển xong: xoá khỏi danh sách $pending');
});
