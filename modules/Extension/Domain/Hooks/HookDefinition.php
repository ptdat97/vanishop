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
        /** `stable` = public API theo compatibility policy; `experimental` = điểm tự động, có thể đổi ở bản minor (ghi CHANGELOG). */
        public string $stability = 'stable',
        /** `fail` = lỗi listener làm hỏng flow (giao dịch rollback); `skip` = bỏ listener lỗi, ghi log (luồng đọc/hiển thị). */
        public string $onError = 'fail',
    ) {}

    /**
     * Khai báo theo mẫu (`vani.admin.page.*`) — bao mọi hook có tiền tố đó.
     */
    public function isPattern(): bool
    {
        return str_ends_with($this->name, '.*');
    }

    public function withName(string $name): self
    {
        return new self($name, $this->type, $this->public, $this->since, $this->args, $this->description, $this->stability, $this->onError);
    }

    /**
     * @param  array{type: string, visibility?: string, since?: string, args?: array<string, string>, description?: string, stability?: string, on_error?: string}  $definition
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
            stability: $definition['stability'] ?? 'stable',
            onError: $definition['on_error'] ?? 'fail',
        );
    }
}
