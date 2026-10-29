<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

final readonly class VideoThumbnail
{
    public function __construct(
        public string $url,
        public ?int $width = null,
        public ?int $height = null,
    ) {}
}
