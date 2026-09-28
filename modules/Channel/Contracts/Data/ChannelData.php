<?php

declare(strict_types=1);

namespace Modules\Channel\Contracts\Data;

final readonly class ChannelData
{
    /**
     * @param  list<int>  $brandIds
     */
    public function __construct(
        public int $id,
        public string $code,
        public string $type,
        public string $locale,
        public string $currencyCode,
        public array $brandIds,
        public string $pathPrefix = '',
    ) {}
}
