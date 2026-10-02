<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Throwable;

/**
 * Bản ảnh thu nhỏ WebP theo chiều rộng cố định (intervention/image, ADR-032) — tạo khi tải ảnh lên, đường dẫn suy ra
 * từ checksum + chiều rộng nên không cần cột DB. Ảnh gốc giữ nguyên. Lỗi xử lý ảnh không chặn việc tải lên (dùng gốc).
 */
final class ImageVariants
{
    /** Chiều rộng: thẻ sản phẩm (400), PDP mobile/desktop (800, 1600). */
    public const WIDTHS = [400, 800, 1600];

    private const QUALITY = 80;

    private const PROCESSABLE = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private readonly ImageManagerInterface $images) {}

    /**
     * @return list<int> chiều rộng đã tạo
     */
    public function generate(string $disk, string $sourcePath, string $checksum, string $mimeType, ?int $width): array
    {
        if (! in_array($mimeType, self::PROCESSABLE, true) || $width === null) {
            return [];
        }

        $created = [];
        try {
            $binary = Storage::disk($disk)->get($sourcePath);
            foreach (self::widthsFor($width) as $target) {
                $encoded = $this->images->decodeBinary($binary)->scaleDown(width: $target)->encode(new WebpEncoder(quality: self::QUALITY));
                Storage::disk($disk)->put(self::path($checksum, $target), (string) $encoded);
                $created[] = $target;
            }
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Không tạo được ảnh thu nhỏ, dùng ảnh gốc.', ['checksum' => $checksum]);
        }

        return $created;
    }

    /**
     * Chiều rộng biến thể có cho ảnh rộng $width (chỉ thu nhỏ, không phóng to).
     *
     * @return list<int>
     */
    public static function widthsFor(int $width): array
    {
        return array_values(array_filter(self::WIDTHS, fn (int $target): bool => $target < $width));
    }

    public static function path(string $checksum, int $width): string
    {
        return 'media/'.substr($checksum, 0, 2)."/{$checksum}-w{$width}.webp";
    }
}
