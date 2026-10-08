<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

/**
 * Phân khúc của một khách: nhóm (tối đa một) + tag.
 */
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
