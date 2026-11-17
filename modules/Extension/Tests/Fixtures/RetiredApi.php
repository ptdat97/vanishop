<?php

declare(strict_types=1);

namespace Modules\Extension\Tests\Fixtures;

use Modules\Shared\Contracts\PluginApi;

/**
 * Class API cho plugin đã ngừng khuyến nghị — dùng trong test quét deprecation.
 */
#[PluginApi(deprecated: 'dùng RetiredApi2; gỡ ở Extension API 2.0')]
final class RetiredApi
{
    public const VERSION = 1;
}
