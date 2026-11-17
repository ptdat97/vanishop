<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Phân khúc của một khách: nhóm (tối đa một) + tag.
 */
#[PluginApi]
final readonly class CustomerSegment
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        public ?int $groupId,
        public ?string $groupCode,
        public ?string $groupName,
        public array $tags,
    ) {}
}
