<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Đọc file bảng tính nhân viên tải lên (nhập mã vận đơn, nhập giá…): `.xlsx` qua SimpleXlsx, còn lại là CSV (UTF-8, có/không
 * BOM; phân cách `,` `;` hoặc tab — Excel VN hay dùng `;`). Dòng trống bị bỏ; tối đa SimpleXlsx::MAX_ROWS dòng.
 */
final class SpreadsheetRows
{
    /**
     * @return list<list<string>>
     *
     * @throws RuntimeException file hỏng, sai mã hoá hoặc quá nhiều dòng (thông điệp cho nhân viên)
     */
    public static function fromUpload(UploadedFile $file, int $maxRows = SimpleXlsx::MAX_ROWS): array
    {
        return strtolower($file->getClientOriginalExtension()) === 'xlsx'
            ? SimpleXlsx::read((string) $file->getRealPath(), $maxRows)
            : self::csv((string) $file->getRealPath(), $maxRows);
    }

    /**
     * @return list<list<string>>
     */
    public static function csv(string $path, int $maxRows = SimpleXlsx::MAX_ROWS): array
    {
        return DelimitedSpreadsheetRows::parse((string) file_get_contents($path), $maxRows);
    }
}
