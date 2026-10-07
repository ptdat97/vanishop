<?php

declare(strict_types=1);

namespace Modules\Catalog\Console;

use Illuminate\Console\Command;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Persistence\Models\Media;
use Throwable;

/**
 * Quản lý cache ảnh thu nhỏ tại public/cache: xoá (tạo lại dần khi có request — cần sau khi bật/tắt plugin định dạng ảnh hoặc đổi chất lượng) hoặc tạo trước (sau deploy).
 */
final class MediaCacheCommand extends Command
{
    protected $signature = 'vani:media:cache
        {--clear : Xoá toàn bộ cache ảnh}
        {--warm : Tạo trước mọi bản thu nhỏ của mọi ảnh}';

    protected $description = 'Xoá hoặc tạo trước cache ảnh thu nhỏ (public/cache)';

    public function handle(ImageCache $cache): int
    {
        if (! $this->option('clear') && ! $this->option('warm')) {
            $this->error('Chọn --clear và/hoặc --warm.');

            return self::INVALID;
        }

        if ($this->option('clear')) {
            $cache->flush();
            $this->info('Đã xoá cache ảnh.');
        }

        if ($this->option('warm')) {
            [$files, $failed] = [0, 0];
            Media::query()->orderBy('id')->chunkById(100, function ($items) use ($cache, &$files, &$failed): void {
                foreach ($items as $media) {
                    try {
                        $files += count($cache->warm($media));
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                        $this->warn("Không xử lý được ảnh #{$media->id} ({$media->original_name}).");
                    }
                }
            });
            $this->info("Đã có {$files} bản thu nhỏ trong cache".($failed > 0 ? ", {$failed} ảnh lỗi" : '').'.');
        }

        return self::SUCCESS;
    }
}
