<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\EncoderInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Modules\Catalog\Contracts\Data\ImageSize;
use Modules\Catalog\Contracts\ImageFormat;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Extension\Contracts\Extensions;

/**
 * Ảnh thu nhỏ tạo khi có request đầu tiên và lưu tại public/cache:
 *
 * - URL `/cache/media/{ab}/{checksum}-{kích thước}.{ext}` (kích thước `w400`, `w400h600`, `w400h600c` — ImageSize::key):
 *   file có sẵn → web server trả tĩnh; chưa có → route Laravel gọi render() rồi trả file (ImageCacheController).
 * - Chỉ các kích thước của ImageSizes (cấu hình Admin + plugin); không phóng to; không có bản phù hợp → ảnh gốc.
 * - Ảnh sản phẩm (`group: product`) cắt theo tỉ lệ cấu hình (mặc định 2:3) để lưới đều nhau; ảnh khác giữ tỉ lệ gốc.
 * - Định dạng giữ như gốc; plugin đóng góp ImageFormat (vd. vani.media-webp) thì dùng định dạng của plugin.
 * - Ảnh gốc định danh theo checksum, kích thước nằm trong tên file → bản cache không bao giờ cũ, không cần so mtime.
 * - Khoá file khi tạo để nhiều request cùng lúc chỉ resize một lần; ghi file tạm rồi rename (không lộ file dở). Disk local
 *   đọc thẳng từ đường dẫn file (không nạp cả ảnh gốc vào bộ nhớ PHP hai lần).
 */
final class ImageCache
{
    /** MIME nguồn xử lý được → đuôi file khi giữ định dạng gốc. */
    public const SOURCES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** Đuôi file đầu ra hợp lệ (khớp route). */
    public const EXTENSIONS = ['jpg', 'png', 'webp', 'avif'];

    /** Mẫu khoá kích thước trong tên file (khớp route). */
    public const KEY_PATTERN = 'w[0-9]{1,4}(?:h[0-9]{1,4}c?)?';

    public function __construct(
        private readonly ImageManagerInterface $images,
        private readonly Extensions $extensions,
        private readonly ImageSizes $sizes,
        private readonly string $directory,
    ) {}

    /**
     * URL ảnh: `$size` số = bản nhỏ nhất rộng ≥ số đó (theo `$group`: null giữ tỉ lệ, `product` cắt theo tỉ lệ sản phẩm);
     * chuỗi = kích thước có tên (plugin). Không có `$size` hoặc không có bản phù hợp → ảnh gốc.
     */
    public function url(Media $media, int|string|null $size = null, ?string $group = null): string
    {
        $variant = match (true) {
            $size === null => null,
            is_string($size) => ($named = $this->sizes->named($size)) !== null && $this->allows($media, $named) ? $named : null,
            default => $this->variantFor($media, $size, $group),
        };

        return $variant === null ? $this->original($media) : $this->cachedUrl($media, $variant);
    }

    /**
     * Giá trị `srcset` theo kích thước đã lưu trong DB — không đọc file; rỗng khi không có bản thu nhỏ. Ảnh giữ tỉ lệ có
     * thêm ảnh gốc; ảnh cắt theo tỉ lệ sản phẩm thì không (gốc khác tỉ lệ làm khung nhảy).
     */
    public function srcset(Media $media, ?string $group = null): string
    {
        $variants = $this->variantsFor($media, $group);
        if ($variants === []) {
            return '';
        }
        $sources = array_map(fn (ImageSize $size): string => $this->cachedUrl($media, $size)." {$size->width}w", $variants);
        if ($media->width !== null && ($group !== ImageSizes::PRODUCT || $this->sizes->productRatio() === null)) {
            $sources[] = $this->original($media)." {$media->width}w";
        }

        return implode(', ', $sources);
    }

    /**
     * Bản cache dùng cho yêu cầu rộng $width (null = dùng ảnh gốc).
     */
    public function variantFor(Media $media, int $width, ?string $group = null): ?ImageSize
    {
        foreach ($this->variantsFor($media, $group) as $variant) {
            if ($variant->width >= $width) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Kích thước theo bề rộng tạo được cho ảnh này (chỉ thu nhỏ, chỉ định dạng xử lý được), tăng dần.
     *
     * @return list<ImageSize>
     */
    public function variantsFor(Media $media, ?string $group = null): array
    {
        return array_values(array_filter($this->sizes->responsive($group), fn (ImageSize $size): bool => $this->allows($media, $size)));
    }

    /**
     * Ảnh này có bản cache kích thước $size không: định dạng xử lý được và không phải phóng to. Cắt khung: phần ảnh
     * đúng tỉ lệ phải rộng ít nhất bằng bản thu nhỏ; giữ tỉ lệ: ảnh gốc phải rộng hơn (bằng thì dùng luôn ảnh gốc).
     */
    public function allows(Media $media, ImageSize $size): bool
    {
        if ($media->width === null || $media->height === null || ! isset(self::SOURCES[$media->mime_type])) {
            return false;
        }
        if ($size->crop && $size->height !== null) {
            return $size->width <= min($media->width, (int) floor($media->height * $size->width / $size->height));
        }

        return $size->width < $media->width || ($size->height !== null && $size->height < $media->height);
    }

    /**
     * Đuôi file bản cache của ảnh: theo plugin ImageFormat nếu có, không thì giữ định dạng gốc.
     */
    public function extensionFor(Media $media): string
    {
        return $this->format($media)?->extension() ?? self::SOURCES[$media->mime_type] ?? 'jpg';
    }

    /**
     * Tạo (nếu chưa có) bản cache rồi trả đường dẫn tuyệt đối; null nếu kích thước/đuôi file không đúng cấu hình hiện tại
     * cho ảnh này. Lỗi đọc/giải mã ảnh được ném ra để nơi gọi quay về ảnh gốc.
     */
    public function render(Media $media, string $key, string $extension): ?string
    {
        $size = $this->sizes->find($key);
        if ($size === null || ! $this->allows($media, $size) || $extension !== $this->extensionFor($media)) {
            return null;
        }

        $target = $this->path($media->checksum, $key, $extension);
        if (is_file($target)) {
            return $target;
        }

        File::ensureDirectoryExists(dirname($target));
        $this->withLock($target, function () use ($media, $size, $target): void {
            clearstatcache(true, $target);
            if (is_file($target)) {
                return;
            }

            $encoded = $this->resize($this->decode($media), $size)->encode($this->encoder($media));
            $temporary = $target.'.'.bin2hex(random_bytes(4)).'.tmp';
            File::put($temporary, (string) $encoded);
            File::move($temporary, $target);
        });

        return $target;
    }

    /**
     * Tạo trước bản cache của ảnh (vani:media:cache --warm): bề rộng chung + kích thước có tên; bản cắt theo tỉ lệ sản
     * phẩm chỉ khi `$product` (ảnh nằm trong gallery màu sản phẩm) — không tạo thừa cho logo, banner, bài viết.
     *
     * @return list<string> khoá kích thước đã có trong cache
     */
    public function warm(Media $media, bool $product = false): array
    {
        $extension = $this->extensionFor($media);
        $sizes = [...$this->sizes->responsive(), ...($product ? $this->sizes->responsive(ImageSizes::PRODUCT) : []), ...$this->sizes->namedSizes()];
        $keys = array_values(array_unique(array_map(fn (ImageSize $size): string => $size->key(), array_filter($sizes, fn (ImageSize $size): bool => $this->allows($media, $size)))));

        return array_values(array_filter($keys, fn (string $key): bool => $this->render($media, $key, $extension) !== null));
    }

    /**
     * Xoá mọi bản thu nhỏ của một ảnh (khi xoá ảnh khỏi thư viện).
     */
    public function forget(Media $media): void
    {
        $checksum = $media->checksum;
        File::delete(glob($this->directory.'/media/'.substr($checksum, 0, 2)."/{$checksum}-w*.*") ?: []);
    }

    /**
     * Xoá toàn bộ cache ảnh; các bản sẽ được tạo lại khi có request (sau khi đổi kích thước/định dạng/chất lượng).
     */
    public function flush(): void
    {
        File::deleteDirectory($this->directory.'/media');
    }

    public function path(string $checksum, string $key, string $extension): string
    {
        return $this->directory.'/'.self::relativePath($checksum, $key, $extension);
    }

    public static function relativePath(string $checksum, string $key, string $extension): string
    {
        return 'media/'.substr($checksum, 0, 2)."/{$checksum}-{$key}.{$extension}";
    }

    private function cachedUrl(Media $media, ImageSize $size): string
    {
        return asset('cache/'.self::relativePath($media->checksum, $size->key(), $this->extensionFor($media)));
    }

    private function original(Media $media): string
    {
        return Storage::disk($media->disk)->url($media->path);
    }

    private function decode(Media $media): ImageInterface
    {
        // Disk local: giải mã thẳng từ file (Imagick/GD đọc trực tiếp) — không nạp chuỗi nhị phân vào PHP.
        if (config("filesystems.disks.{$media->disk}.driver") === 'local') {
            $path = Storage::disk($media->disk)->path($media->path);
            if (is_file($path)) {
                return $this->images->decodePath($path);
            }
        }

        return $this->images->decodeBinary((string) Storage::disk($media->disk)->get($media->path));
    }

    private function resize(ImageInterface $image, ImageSize $size): ImageInterface
    {
        if ($size->crop && $size->height !== null) {
            return $image->coverDown($size->width, $size->height);
        }

        return $image->scaleDown(width: $size->width, height: $size->height);
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
        $quality = $this->sizes->quality();

        return $this->format($media)?->encoder($quality) ?? match (self::SOURCES[$media->mime_type]) {
            'png' => new PngEncoder,
            'webp' => new WebpEncoder(quality: $quality),
            default => new JpegEncoder(quality: $quality, progressive: true),
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
