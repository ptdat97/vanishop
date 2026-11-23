<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Shared\Contracts\PluginApi;

/** Chuyển cấu hình cũ sang schema hiện hành để hiển thị, sửa hoặc nhập lại. */
#[PluginApi]
interface NormalizesBlockConfiguration extends StorefrontBlock
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function normalize(array $config): array;
}
