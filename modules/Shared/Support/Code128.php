<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Mã vạch Code 128 (bộ mã B: ký tự ASCII 32–126) dạng SVG — in trên phiếu để máy quét đọc lại đúng chuỗi đã mã hoá
 * (số đơn, mã vận đơn…). Không phụ thuộc thư viện ngoài; SVG chỉ gồm các thanh `<rect>`, không chứa chuỗi đầu vào.
 */
final class Code128
{
    private const START_B = 104;

    private const STOP = 106;

    /** Độ rộng thanh/khoảng trắng xen kẽ (bắt đầu bằng thanh) của từng giá trị 0–106 theo ISO/IEC 15417. */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    /**
     * Dãy giá trị ký hiệu: Start B, dữ liệu, ký tự kiểm tra, Stop. Null khi chuỗi rỗng, dài quá 48 ký tự hoặc có ký tự
     * ngoài ASCII 32–126.
     *
     * @return list<int>|null
     */
    public static function symbols(string $text): ?array
    {
        if ($text === '' || strlen($text) > 48 || preg_match('/^[\x20-\x7E]+$/', $text) !== 1) {
            return null;
        }

        $values = array_map(fn (string $character): int => ord($character) - 32, str_split($text));
        $checksum = self::START_B;
        foreach ($values as $index => $value) {
            $checksum += $value * ($index + 1);
        }

        return [self::START_B, ...$values, $checksum % 103, self::STOP];
    }

    /**
     * Dãy module: `1` = thanh đen, `0` = khoảng trắng.
     */
    public static function modules(string $text): ?string
    {
        $symbols = self::symbols($text);
        if ($symbols === null) {
            return null;
        }

        $modules = '';
        foreach ($symbols as $symbol) {
            foreach (str_split(self::PATTERNS[$symbol]) as $position => $width) {
                $modules .= str_repeat($position % 2 === 0 ? '1' : '0', (int) $width);
            }
        }

        return $modules;
    }

    /**
     * SVG co giãn theo chiều ngang của vùng chứa, có vùng trắng 10 module hai bên. Null khi không mã hoá được.
     */
    public static function svg(string $text, int $height = 40): ?string
    {
        $modules = self::modules($text);
        if ($modules === null) {
            return null;
        }

        $quiet = 10;
        $width = strlen($modules) + 2 * $quiet;
        $bars = '';
        preg_match_all('/1+/', $modules, $runs, PREG_OFFSET_CAPTURE);
        foreach ($runs[0] as [$run, $offset]) {
            $bars .= '<rect x="'.($offset + $quiet).'" y="0" width="'.strlen($run).'" height="'.$height.'"/>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' '.$height.'" preserveAspectRatio="none" role="img" aria-hidden="true" shape-rendering="crispEdges">'
            .'<rect width="'.$width.'" height="'.$height.'" fill="#fff"/><g fill="#000">'.$bars.'</g></svg>';
    }
}
