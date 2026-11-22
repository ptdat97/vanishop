<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

/** Đọc kích thước video từ metadata với giới hạn đọc cố định. */
final class VideoProbe
{
    private const WINDOW = 4 * 1024 * 1024;

    /**
     * @return array{int, int}|null [rộng, cao]
     */
    public static function dimensions(string $path, string $mimeType): ?array
    {
        if (! in_array($mimeType, ['video/mp4', 'video/webm'], true) || ! is_file($path)) {
            return null;
        }
        $size = (int) filesize($path);
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }
        try {
            $chunks = [(string) fread($handle, self::WINDOW)];
            if ($mimeType === 'video/webm') {
                return self::webmDimensions($chunks[0]);
            }
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

    /**
     * @return array{int, int}|null
     */
    private static function webmDimensions(string $data): ?array
    {
        $fileEnd = strlen($data);
        foreach (self::elements($data, 0, $fileEnd) as [$id, $segmentStart, $segmentEnd]) {
            if ($id !== 0x18538067) { // Segment
                continue;
            }
            foreach (self::elements($data, $segmentStart, $segmentEnd) as [$id, $tracksStart, $tracksEnd]) {
                if ($id !== 0x1654AE6B) { // Tracks
                    continue;
                }
                foreach (self::elements($data, $tracksStart, $tracksEnd) as [$id, $trackStart, $trackEnd]) {
                    if ($id === 0xAE && ($dimensions = self::webmTrack($data, $trackStart, $trackEnd)) !== null) {
                        return $dimensions;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array{int, int}|null
     */
    private static function webmTrack(string $data, int $start, int $end): ?array
    {
        $type = $width = $height = 0;
        foreach (self::elements($data, $start, $end) as [$id, $start, $end]) {
            if ($id === 0x83 && $end - $start === 1) {
                $type = ord($data[$start]);
            }
            if ($id === 0xE0) { // Video settings
                foreach (self::elements($data, $start, $end) as [$id, $start, $end]) {
                    $length = $end - $start;
                    if (($id !== 0xB0 && $id !== 0xBA) || $length < 1 || $length > 4) {
                        continue;
                    }
                    $value = unpack('N', str_pad(substr($data, $start, $length), 4, "\0", STR_PAD_LEFT))[1];
                    if ($id === 0xB0) {
                        $width = (int) $value;
                    } else {
                        $height = (int) $value;
                    }
                }
            }
        }

        return $type === 1 && $width > 0 && $height > 0 && $width <= 16384 && $height <= 16384 ? [$width, $height] : null;
    }

    /**
     * EBML ID giữ bit đánh dấu, size bỏ bit này. Chỉ Segment có thể vượt quá cửa sổ đọc hoặc có size không xác định.
     *
     * @return iterable<array{int, int, int}>
     */
    private static function elements(string $data, int $offset, int $end): iterable
    {
        while ($offset < $end) {
            $id = self::vint($data, $offset, $end, true);
            $size = $id === null ? null : self::vint($data, $offset, $end, false);
            if ($id === null || $size === null) {
                return;
            }
            if (($size < 0 || $size > $end - $offset) && $id !== 0x18538067) {
                return;
            }
            $next = $size < 0 ? $end : min($end, $offset + $size);
            yield [$id, $offset, $next];
            $offset = $next;
        }
    }

    /** -1 đánh dấu size không xác định, null là header hỏng hoặc thiếu. */
    private static function vint(string $data, int &$offset, int $end, bool $id): ?int
    {
        if ($offset >= $end || ($first = ord($data[$offset])) === 0) {
            return null;
        }
        $mask = 0x80;
        $length = 1;
        while (($first & $mask) === 0) {
            $mask >>= 1;
            $length++;
        }
        if ($length > ($id ? 4 : 8) || $length > $end - $offset) {
            return null;
        }
        $value = $id ? $first : $first & ($mask - 1);
        $offset++;
        for ($byte = 1; $byte < $length; $byte++) {
            $value = ($value << 8) | ord($data[$offset++]);
        }

        return ! $id && $value === (1 << (7 * $length)) - 1 ? -1 : $value;
    }
}
