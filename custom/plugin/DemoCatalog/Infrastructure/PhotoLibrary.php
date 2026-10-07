<?php

declare(strict_types=1);

namespace Plugin\DemoCatalog\Infrastructure;

use InvalidArgumentException;

/**
 * Đọc thư mục ảnh chụp sản phẩm và gom thành sản phẩm demo:
 * - chuẩn hoá tên (`_1773799834VNQ07102 copy_1.jpg`, `vnq07102-copy-1776485158.jpg` → `vnq07102`) để bỏ ảnh tải lên
 *   nhiều lần — giữ file lớn nhất (bản gốc);
 * - ảnh cùng tiền tố máy chụp với số khung liền nhau (≤ 40) là cùng một bộ đồ → một sản phẩm, tối đa 4 ảnh.
 */
final class PhotoLibrary
{
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const FRAME_GAP = 40;

    private const MAX_PER_PRODUCT = 4;

    /**
     * @return list<array{key: string, files: list<string>}> key ổn định (vd. `vnq6565`) — dùng làm mã sản phẩm
     */
    public function groups(string $directory): array
    {
        if (! is_dir($directory)) {
            throw new InvalidArgumentException("Không thấy thư mục ảnh demo: {$directory} (đặt VANI_DEMO_IMAGES hoặc --source).");
        }

        $best = [];
        foreach (scandir($directory) ?: [] as $name) {
            $path = $directory.DIRECTORY_SEPARATOR.$name;
            if (! is_file($path) || ! in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                continue;
            }
            $key = self::normalize($name);
            if (! isset($best[$key]) || filesize($path) > filesize($best[$key])) {
                $best[$key] = $path;
            }
        }

        $frames = [];
        foreach ($best as $key => $path) {
            preg_match('/^([a-z]+)(\d+)$/', $key, $match);
            $frames[] = ['prefix' => $match[1] ?? $key, 'number' => (int) ($match[2] ?? 0), 'path' => $path];
        }
        usort($frames, fn (array $a, array $b): int => [$a['prefix'], $a['number'], $a['path']] <=> [$b['prefix'], $b['number'], $b['path']]);

        $groups = [];
        foreach ($frames as $frame) {
            $last = array_key_last($groups);
            $current = $last === null ? null : $groups[$last];
            if ($current !== null && $current['prefix'] === $frame['prefix'] && $frame['number'] - $current['start'] <= self::FRAME_GAP && count($current['files']) < self::MAX_PER_PRODUCT) {
                $groups[$last]['files'][] = $frame['path'];

                continue;
            }
            $groups[] = ['prefix' => $frame['prefix'], 'start' => $frame['number'], 'files' => [$frame['path']]];
        }

        return array_map(fn (array $group): array => ['key' => $group['prefix'].$group['start'], 'files' => $group['files']], $groups);
    }

    public static function normalize(string $fileName): string
    {
        $name = ltrim(strtolower(pathinfo($fileName, PATHINFO_FILENAME)), '_');
        $name = (string) preg_replace('/^\d{9,}_?/', '', $name);      // tiền tố thời điểm tải lên
        $name = (string) preg_replace('/-\d{9,}$/', '', $name);        // hậu tố thời điểm tải lên
        $name = (string) preg_replace('/_1$/', '', $name);
        $name = (string) preg_replace('/[\s-]*copy/', '', $name);

        return (string) preg_replace('/[^a-z0-9]/', '', $name);
    }
}
