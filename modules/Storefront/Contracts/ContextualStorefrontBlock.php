<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Shared\Contracts\PluginApi;
use Modules\Storefront\Contracts\Data\BlockContext;

/** Khối Builder đặt ở slot cụ thể, được render với ngữ cảnh công khai của slot. */
#[PluginApi]
interface ContextualStorefrontBlock extends StorefrontBlock
{
    /** @return list<string> */
    public function slots(): array;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null null = bỏ qua khối trong ngữ cảnh này
     */
    public function resolveInContext(array $config, string $locale, BlockContext $context): ?array;
}
