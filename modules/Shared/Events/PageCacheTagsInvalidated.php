<?php

declare(strict_types=1);

namespace Modules\Shared\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Shared\Contracts\PluginApi;

/**
 * Cache trang của các tag này vừa hết hiệu lực (ADR-051) — lô xoá mỗi phút, xoá ngay hoặc nút "Xoá cache trang". Plugin
 * CDN nghe để xoá bản ở edge theo tag (header tag ở chế độ `cdn`). `$tags` chứa `*` khi xoá toàn bộ.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
final readonly class PageCacheTagsInvalidated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<string>  $tags
     */
    public function __construct(public array $tags) {}

    public function everything(): bool
    {
        return in_array('*', $this->tags, true);
    }
}
