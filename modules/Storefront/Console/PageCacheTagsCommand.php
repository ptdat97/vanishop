<?php

declare(strict_types=1);

namespace Modules\Storefront\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Storefront\Http\Middleware\PublicPageCache;

/**
 * Gỡ lỗi cache trang (ADR-051 §8): mở một URL công khai như khách vãng lai, in trạng thái cache và các tag của trang.
 */
final class PageCacheTagsCommand extends Command
{
    protected $signature = 'vani:page-cache:tags {url : Đường dẫn hoặc URL đầy đủ của trang công khai}';

    protected $description = 'Xem trạng thái cache và tag của một trang công khai';

    public function handle(Kernel $kernel, BatchedPageCacheInvalidator $invalidator): int
    {
        $url = (string) $this->argument('url');
        $request = Request::create(str_starts_with($url, 'http') ? $url : url($url));
        $response = $kernel->handle($request);

        $this->line('Trạng thái: '.$response->getStatusCode().' '.($response->headers->get(PublicPageCache::HEADER) ?? '— (không phải trang công khai)')
            .(($reason = $response->headers->get(PublicPageCache::HEADER.'-Reason')) !== null ? " ({$reason})" : ''));
        $tags = (array) $request->attributes->get(PublicPageCache::TAGS_ATTRIBUTE, []);
        $this->line('Tag ('.count($tags).'): '.($tags === [] ? '—' : implode(', ', $tags)));
        $pending = array_intersect($invalidator->pending(), $tags);
        if ($pending !== []) {
            $this->warn('Đang chờ xoá ở lô kế tiếp: '.implode(', ', $pending));
        }
        $kernel->terminate($request, $response);

        return self::SUCCESS;
    }
}
