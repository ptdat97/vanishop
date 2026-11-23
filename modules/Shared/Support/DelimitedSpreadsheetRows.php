<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use RuntimeException;

/** @internal Đọc CSV và dữ liệu ô dán từ bảng tính. */
final class DelimitedSpreadsheetRows
{
    /** @return list<list<string>> */
    public static function parse(string $content, int $maxRows = SimpleXlsx::MAX_ROWS): array
    {
        $content = str_starts_with($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;
        if (! mb_check_encoding($content, 'UTF-8')) {
            throw new RuntimeException('File CSV phải lưu dạng UTF-8.');
        }
        $first = ltrim($content, "\r\n");
        $first = strtok($first, "\r\n") ?: '';
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn (string $candidate): int => substr_count($first, $candidate))->first();
        $rows = [];
        foreach (preg_split('/\r\n|\n|\r/', $content) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = array_map('strval', str_getcsv($line, $delimiter, '"', ''));
            if (count($rows) > $maxRows) {
                throw new RuntimeException('File có quá '.$maxRows.' dòng.');
            }
        }

        return $rows;
    }
}
