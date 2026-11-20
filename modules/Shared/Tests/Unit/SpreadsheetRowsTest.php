<?php

use Modules\Shared\Support\SimpleXlsx;
use Modules\Shared\Support\SpreadsheetRows;

/*
| Trần số dòng khi đọc file: mặc định SimpleXlsx::MAX_ROWS (5.000); màn hình nhập file sản phẩm truyền trần lớn hơn (chạy nền).
*/

it('quá trần mặc định bị từ chối; truyền trần lớn hơn thì đọc được (csv và xlsx)', function () {
    $dir = sys_get_temp_dir().'/vani-rows-'.uniqid();
    mkdir($dir);
    $rows = array_map(fn (int $i): array => ["SP{$i}", "Áo {$i}"], range(1, SimpleXlsx::MAX_ROWS + 1));
    file_put_contents("{$dir}/a.csv", implode("\n", array_map(fn (array $row): string => implode(',', $row), $rows)));
    SimpleXlsx::write("{$dir}/a.xlsx", $rows, 'Sheet1');

    expect(fn () => SpreadsheetRows::csv("{$dir}/a.csv"))->toThrow(RuntimeException::class)
        ->and(fn () => SimpleXlsx::read("{$dir}/a.xlsx"))->toThrow(RuntimeException::class)
        ->and(SpreadsheetRows::csv("{$dir}/a.csv", 60_000))->toHaveCount(SimpleXlsx::MAX_ROWS + 1)
        ->and(SimpleXlsx::read("{$dir}/a.xlsx", 60_000)[SimpleXlsx::MAX_ROWS])->toBe(['SP5001', 'Áo 5001']);

    array_map('unlink', glob("{$dir}/*") ?: []);
    rmdir($dir);
});
