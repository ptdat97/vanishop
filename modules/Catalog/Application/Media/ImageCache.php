<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\EncoderInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Modules\Catalog\Contracts\ImageFormat;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Extension\Contracts\Extensions;

/**
 * Ảnh thu nhỏ theo chiều rộng, tạo khi có request đầu tiên và lưu tại public/cache:
 *
 * - URL `/cache/media/{ab}/{checksum}-w{width}.{ext}`: file có sẵn → web server trả tĩnh; chưa có → route Laravel
 *   gọi render() rồi trả file (ImageCacheController).
 * - Chỉ chiều rộng trong cấu hình và nhỏ hơn ảnh gốc (không phóng to); không có bản phù hợp → URL ảnh gốc.
 * - Định dạng giữ như gốc; plugin đóng góp ImageFormat (vd. vani.media-webp) thì dùng định dạng của plugin.
 * - Ảnh gốc định danh theo checksum (không đổi nội dung) nên bản cache không bao giờ cũ — không cần so mtime.
 * - Khoá file khi tạo để nhiều request cùng lúc chỉ resize một lần; ghi file tạm rồi rename (không lộ file dở).
 */
final class ImageCache
{
    /** MIME nguồn xử lý được → đuôi file khi giữ định dạng gốc. */
    public const SOURCES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** Đuôi file đầu ra hợp lệ (khớp route). */
    public const EXTENSIONS = ['jpg', 'png', 'webp', 'avif'];

    /**
     * @param  list<int>  $widths
     * @param  int<1, 100>  $quality
     */
    public function __construct(
        private readonly ImageManagerInterface $images,
        private readonly Extensions $extensions,
        private readonly string $directory,
        private readonly array $widths,
        private readonly int $quality = 80,
    ) {}

    /**
     * URL bản nhỏ nhất rộng ≥ $width; không có $width hoặc không có bản phù hợp → ảnh gốc.
     */
    public function url(Media $media, ?int $width = null): string
    {
        $variant = $width === null ? null : $this->variantFor($media, $width);

        return $variant === null
            ? Storage::disk($media->disk)->url($media->path)
            : asset('cache/'.self::relativePath($media->checksum, $variant, $this->extensionFor($media)));
    }

    /**
     * Chiều rộng bản cache dùng cho yêu cầu rộng $width (null = dùng ảnh gốc).
     */
    public function variantFor(Media $media, int $width): ?int
    {
        foreach ($this->widthsFor($media) as $variant) {
            if ($variant >= $width) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Các chiều rộng được phép cho ảnh này (chỉ thu nhỏ, chỉ định dạng xử lý được).
     *
     * @return list<int>
     */
    public function widthsFor(Media $media): array
    {
        if ($media->width === null || ! isset(self::SOURCES[$media->mime_type])) {
            return [];
        }

        $widths = array_filter($this->widths, fn (int $variant): bool => $variant < $media->width);
        sort($widths);

        return $widths;
    }

    /**
     * Đuôi file bản cache của ảnh: theo plugin ImageFormat nếu có, không thì giữ định dạng gốc.
     */
    public function extensionFor(Media $media): string
    {
        return $this->format($media)?->extension() ?? self::SOURCES[$media->mime_type] ?? 'jpg';
    }

    /**
     * Tạo (nếu chưa có) bản cache rồi trả đường dẫn tuyệt đối; null nếu chiều rộng/đuôi file không đúng với cấu hình
     * hiện tại cho ảnh này. Lỗi đọc/giải mã ảnh được ném ra để nơi gọi quay về ảnh gốc.
     */
    public function render(Media $media, int $width, string $extension): ?string
    {
        if (! in_array($width, $this->widthsFor($media), true) || $extension !== $this->extensionFor($media)) {
            return null;
        }

        $target = $this->path($media->checksum, $width, $extension);
        if (is_file($target)) {
            return $target;
        }

        File::ensureDirectoryExists(dirname($target));
        $this->withLock($target, function () use ($media, $width, $target): void {
            clearstatcache(true, $target);
            if (is_file($target)) {
                return;
            }

            $encoded = $this->images->decodeBinary((string) Storage::disk($media->disk)->get($media->path))
                ->scaleDown(width: $width)
                ->encode($this->encoder($media));

            $temporary = $target.'.'.bin2hex(random_bytes(4)).'.tmp';
            File::put($temporary, (string) $encoded);
            File::move($temporary, $target);
        });

        return $target;
    }

    /**
     * Tạo trước mọi bản cache của ảnh (vani:media:cache --warm).
     *
     * @return list<int> chiều rộng đã có trong cache
     */
    public function warm(Media $media): array
    {
        $extension = $this->extensionFor($media);

        return array_values(array_filter($this->widthsFor($media), fn (int $width): bool => $this->render($media, $width, $extension) !== null));
    }

    /**
     * Xoá toàn bộ cache ảnh; các bản sẽ được tạo lại khi có request (sau khi đổi định dạng/chất lượng).
     */
    public function flush(): void
    {
        File::deleteDirectory($this->directory.'/media');
    }

    public function path(string $checksum, int $width, string $extension): string
    {
        return $this->directory.'/'.self::relativePath($checksum, $width, $extension);
    }

    public static function relativePath(string $checksum, int $width, string $extension): string
    {
        return 'media/'.substr($checksum, 0, 2)."/{$checksum}-w{$width}.{$extension}";
    }

    private function format(Media $media): ?ImageFormat
    {
        foreach ($this->extensions->implementations(ImageFormat::TAG, ImageFormat::class) as $format) {
            if ($format->supports($media->mime_type) && in_array($format->extension(), self::EXTENSIONS, true)) {
                return $format;
            }
        }

        return null;
    }

    private function encoder(Media $media): EncoderInterface
    {
        return $this->format($media)?->encoder($this->quality) ?? match (self::SOURCES[$media->mime_type]) {
            'png' => new PngEncoder,
            'webp' => new WebpEncoder(quality: $this->quality),
            default => new JpegEncoder(quality: $this->quality),
        };
    }

    /**
     * @param  callable(): void  $callback
     */
    private function withLock(string $target, callable $callback): void
    {
        $lockPath = $target.'.lock';
        $handle = @fopen($lockPath, 'c');
        if ($handle === false) {
            $callback();

            return;
        }

        try {
            flock($handle, LOCK_EX);
            $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
            @unlink($lockPath);
        }
    }
}
