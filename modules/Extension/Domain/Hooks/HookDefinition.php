<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Hooks;

final readonly class HookDefinition
{
    /**
     * @param  array<string, string>  $args
     */
    public function __construct(
        public string $name,
        public HookType $type,
        public bool $public,
        public string $since,
        public array $args = [],
        public string $description = '',
    ) {}

    /**
     * @param  array{type: string, visibility?: string, since?: string, args?: array<string, string>, description?: string}  $definition
     */
    public static function fromArray(string $name, array $definition): self
    {
        return new self(
            name: $name,
            type: HookType::from($definition['type']),
            public: ($definition['visibility'] ?? 'public') === 'public',
            since: $definition['since'] ?? '0.1',
            args: $definition['args'] ?? [],
            description: $definition['description'] ?? '',
        );
    }
}
