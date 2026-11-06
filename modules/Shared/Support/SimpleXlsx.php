<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Bảng tính .xlsx tối giản (0.3.97) — một sheet, chỉ giá trị (chữ, số), không công thức/định dạng ngoài dòng tiêu đề đậm.
 * Dùng cho file trao đổi với hãng vận chuyển: ô chữ giữ nguyên số 0 đầu SĐT (CSV mở bằng Excel làm mất), và đọc được file
 * Excel do hãng xuất. Không thêm phụ thuộc: ZipArchive + XML.
 */
final class SimpleXlsx
{
    /** Giới hạn khi đọc file tải lên: số dòng và kích thước XML giải nén của sheet. */
    public const MAX_ROWS = 5000;

    private const MAX_SHEET_BYTES = 20 * 1024 * 1024;

    /**
     * Ghi bảng tính ra file. Dòng đầu là tiêu đề (in đậm). Giá trị int/float là ô số; còn lại là ô chữ.
     *
     * @param  list<list<string|int|float|null>>  $rows
     */
    public static function write(string $path, array $rows, string $sheetName = 'Sheet1'): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Không tạo được file {$path}.");
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.self::xml(mb_substr($sheetName, 0, 31)).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            .'</styleSheet>');

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach (array_values($rows) as $index => $row) {
            $number = $index + 1;
            $sheet .= '<row r="'.$number.'">';
            foreach (array_values($row) as $column => $value) {
                $reference = self::column($column).$number;
                $style = $index === 0 ? ' s="1"' : '';
                if ($value === null || $value === '') {
                    continue;
                }
                $sheet .= is_int($value) || is_float($value)
                    ? '<c r="'.$reference.'"'.$style.'><v>'.$value.'</v></c>'
                    : '<c r="'.$reference.'"'.$style.' t="inlineStr"><is><t xml:space="preserve">'.self::xml((string) $value).'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet.'</sheetData></worksheet>');
        $zip->close();
    }

    /**
     * Đọc sheet đầu tiên thành các dòng giá trị chữ (ô trống = ''). Dòng trống bị bỏ.
     *
     * @return list<list<string>>
     */
    public static function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('File không phải bảng tính .xlsx hợp lệ.');
        }
        try {
            $sheetPath = self::firstSheetPath($zip);
            $stat = $zip->statName($sheetPath);
            if ($stat === false || $stat['size'] > self::MAX_SHEET_BYTES) {
                throw new RuntimeException('Bảng tính quá lớn hoặc không có sheet.');
            }
            $strings = [];
            $shared = $zip->getFromName('xl/sharedStrings.xml');
            if ($shared !== false) {
                foreach (self::parse($shared)->si as $item) {
                    $strings[] = self::text($item);
                }
            }
            $rows = [];
            foreach (self::parse((string) $zip->getFromName($sheetPath))->sheetData->row as $row) {
                $values = [];
                foreach ($row->c as $cell) {
                    $index = self::columnIndex(preg_replace('/\d+/', '', (string) $cell['r']) ?: 'A');
                    $type = (string) $cell['t'];
                    $values[$index] = match ($type) {
                        's' => $strings[(int) $cell->v] ?? '',
                        'inlineStr' => self::text($cell->is),
                        default => (string) $cell->v,
                    };
                }
                if (array_filter($values, fn (string $value): bool => trim($value) !== '') === []) {
                    continue;
                }
                $line = array_fill(0, max(array_keys($values)) + 1, '');
                foreach ($values as $index => $value) {
                    $line[$index] = $value;
                }
                $rows[] = $line;
                if (count($rows) > self::MAX_ROWS) {
                    throw new RuntimeException('Bảng tính có quá '.self::MAX_ROWS.' dòng.');
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private static function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $relations = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook !== false && $relations !== false) {
            $sheet = self::parse($workbook)->sheets->sheet[0] ?? null;
            $id = $sheet === null ? '' : (string) ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '');
            foreach (self::parse($relations)->Relationship as $relation) {
                if ((string) $relation['Id'] === $id) {
                    $target = ltrim((string) $relation['Target'], '/');

                    return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function parse(string $xml): SimpleXMLElement
    {
        $element = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
        if ($element === false) {
            throw new RuntimeException('Bảng tính bị hỏng.');
        }

        return $element;
    }

    /** Chữ của ô (gồm các đoạn định dạng `r/t`). */
    private static function text(?SimpleXMLElement $item): string
    {
        if ($item === null) {
            return '';
        }
        if (isset($item->t)) {
            return (string) $item->t;
        }
        $text = '';
        foreach ($item->r as $run) {
            $text .= (string) $run->t;
        }

        return $text;
    }

    private static function column(int $index): string
    {
        $name = '';
        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $name = chr(65 + ($index - 1) % 26).$name;
        }

        return $name;
    }

    private static function columnIndex(string $name): int
    {
        $index = 0;
        foreach (str_split(strtoupper($name)) as $char) {
            $index = $index * 26 + (ord($char) - 64);
        }

        return $index - 1;
    }

    private static function xml(string $value): string
    {
        // Bỏ ký tự điều khiển XML không cho phép.
        return htmlspecialchars((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
