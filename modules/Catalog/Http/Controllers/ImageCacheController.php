<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Persistence\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Chỉ chạy khi file cache chưa có (có rồi thì web server trả file tĩnh): tạo bản thu nhỏ rồi trả về.
 * Chiều rộng/đuôi file không đúng cấu hình hiện tại hoặc ảnh không tồn tại → 404; lỗi xử lý ảnh → chuyển hướng về ảnh gốc.
 */
final class ImageCacheController
{
    public function __invoke(string $shard, string $checksum, int $width, string $extension, ImageCache $cache): BinaryFileResponse|RedirectResponse
    {
        abort_unless(str_starts_with($checksum, $shard), 404);
        $media = Media::query()->where('checksum', $checksum)->first();
        abort_if($media === null, 404);

        try {
            $path = $cache->render($media, $width, $extension);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->away($media->url());
        }
        abort_if($path === null, 404);

        return response()->file($path, [
            'Content-Type' => 'image/'.($extension === 'jpg' ? 'jpeg' : $extension),
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
