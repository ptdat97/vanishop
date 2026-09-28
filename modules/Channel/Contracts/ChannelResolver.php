<?php

declare(strict_types=1);

namespace Modules\Channel\Contracts;

use Modules\Channel\Contracts\Data\ChannelData;

interface ChannelResolver
{
    /**
     * Tìm kênh đang hoạt động theo host và đường dẫn (khớp path prefix dài nhất).
     */
    public function resolve(string $host, string $path): ?ChannelData;

    /**
     * Tìm kênh đang hoạt động theo mã (dùng cho Storefront API: header X-Vani-Channel).
     */
    public function byCode(string $code): ?ChannelData;
}
