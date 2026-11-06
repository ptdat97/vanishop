<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

/**
 * Kích thước khung hình của video MP4 (0.3.91) — đọc hộp `tkhd` (track header) trong 4 MB đầu và cuối file (moov ở đầu
 * khi "faststart", ở cuối khi không), thuần PHP, không cần ffprobe. Track âm thanh có rộng/cao 0 nên lấy track đầu tiên
 * có kích thước. Không đọc được (WebM, file lạ) → null; thư viện vẫn lưu video, chỉ thiếu tỉ lệ khung.
 */
final class VideoProbe
{
    private const WINDOW = 4 * 1024 * 1024;

    /**
     * @return array{int, int}|null [rộng, cao]
     */
    public static function dimensions(string $path, string $mimeType): ?array
    {
        if ($mimeType !== 'video/mp4' || ! is_file($path)) {
            return null;
        }
        $size = (int) filesize($path);
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }
        try {
            $chunks = [(string) fread($handle, self::WINDOW)];
            if ($size > self::WINDOW) {
                fseek($handle, max(self::WINDOW, $size - self::WINDOW));
                $chunks[] = (string) fread($handle, self::WINDOW);
            }
        } finally {
            fclose($handle);
        }

        foreach ($chunks as $data) {
            $offset = 0;
            while (($position = strpos($data, 'tkhd', $offset)) !== false) {
                $offset = $position + 4;
                $version = ord($data[$position + 4] ?? "\0");
                // version 0: thời gian 4 byte; version 1: 8 byte. Rộng/cao là số 16.16 ở cuối hộp.
                $widthAt = $position + 4 + 4 + ($version === 1 ? 32 : 20) + 8 + 8 + 36;
                if (strlen($data) < $widthAt + 8) {
                    continue;
                }
                $width = intdiv((int) unpack('N', substr($data, $widthAt, 4))[1], 65536);
                $height = intdiv((int) unpack('N', substr($data, $widthAt + 4, 4))[1], 65536);
                if ($width > 0 && $height > 0 && $width <= 16384 && $height <= 16384) {
                    return [$width, $height];
                }
            }
        }

        return null;
    }
}
