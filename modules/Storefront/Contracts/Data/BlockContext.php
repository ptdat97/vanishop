<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/** Dữ liệu công khai theo URL. Không mang request, giỏ, khách hoặc dữ liệu phiên. */
#[PluginApi]
final readonly class BlockContext
{
    public function __construct(
        public string $slot,
        public ?int $productId = null,
        public ?string $listingType = null,
        public ?string $listingSlug = null,
    ) {}
}
