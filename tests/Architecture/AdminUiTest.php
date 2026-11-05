<?php

/*
| ADR-039: Admin dùng Reka UI qua bộ component `@admin/Components`. Trang và component của module/plugin chỉ dùng bộ
| component đó (đổi thư viện bên dưới không làm vỡ trang plugin), không dùng hộp thoại của trình duyệt, không ghi cứng
| ngôn ngữ/ký hiệu tiền (MoneyText, useMoney, useDateTime) và — với module Core — không ghi cứng nhãn trạng thái
| (server dịch qua `<module>::labels`).
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

/**
 * File (ngoài bộ component dùng chung) có dòng khớp mẫu.
 *
 * @return list<string>
 */
function adminUiOffenders(string $pattern, bool $coreOnly = false): array
{
    $offenders = [];
    foreach (adminUiFiles() as $file) {
        if (str_starts_with($file, 'resources/js/admin/') || ($coreOnly && ! str_starts_with($file, 'modules/'))) {
            continue;
        }
        foreach (file(base_path($file)) ?: [] as $number => $line) {
            if (preg_match($pattern, $line) === 1) {
                $offenders[] = "{$file}:".($number + 1);
            }
        }
    }

    return $offenders;
}

it('chỉ bộ component @admin/Components được import reka-ui', function () {
    $offenders = array_values(array_filter(
        adminUiFiles(),
        fn (string $file): bool => ! str_starts_with($file, 'resources/js/admin/Components/') && str_contains((string) file_get_contents(base_path($file)), "from 'reka-ui'"),
    ));

    expect($offenders)->toBe([]);
});

it('giao diện Admin không ghi cứng ngôn ngữ hay ký hiệu tiền tệ', function () {
    $offenders = array_values(array_filter(adminUiFiles(), fn (string $file): bool => preg_match('/vi-VN|₫/u', (string) file_get_contents(base_path($file))) === 1));

    expect($offenders)->toBe([])
        ->and(adminUiOffenders('/\bIntl\.|\.toLocale(String|DateString|TimeString)\(/'))->toBe([], 'Định dạng tiền/ngày: MoneyText, useMoney() hoặc useDateTime()');
});

it('trang module/plugin dùng component chung thay cho phần tử thô và hộp thoại của trình duyệt', function () {
    expect(adminUiOffenders('/<(select|table)[\s>]/'))->toBe([], 'Dùng Select/MultiSelect/Combobox/DataTable')
        ->and(adminUiOffenders('/type="(date|datetime-local)"/'))->toBe([], 'Dùng DateInput')
        ->and(adminUiOffenders('/(?<![\w.`])(window\.)?(confirm|prompt|alert)\(/'))->toBe([], 'Dùng confirmAction() hoặc promptFields()');
});

it('không structuredClone dữ liệu trang (props của Inertia là Proxy phản ứng → DataCloneError, lỗi menu 2026-11-05)', function () {
    expect(adminUiOffenders('/\\bstructuredClone\\(/'))->toBe([], 'Chép dữ liệu JSON bằng JSON.parse(JSON.stringify(...)) hoặc toRaw() rồi chép');
});

it('module Core không ghi cứng bảng nhãn trạng thái (server dịch qua <module>::labels)', function () {
    expect(adminUiOffenders('/(Labels|labels)\s*(:[^=]*)?=\s*\{/', coreOnly: true))->toBe([]);
});
