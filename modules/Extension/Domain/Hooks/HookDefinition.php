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
        /** Thông điệp khi hook đã ngừng khuyến nghị (`deprecated` trong hooks.php, K3/P5): plugin nghe hook → log `vani.deprecation`. */
        public ?string $deprecated = null,
        /**
         * Hook có thể chạy TRONG transaction DB (khoá `transaction: true`, rút từ "modifier" của Lunar 2.x): listener chỉ ghi DB
         * của mình, không I/O mạng (gọi HTTP bị ExternalCalls chặn), lỗi → rollback. HookManager (strict) báo lỗi khi hook
         * không khai báo mà lại chạy trong transaction — khai báo luôn khớp thực tế.
         */
        public bool $transaction = false,
        /**
         * Slot nhận block của page builder (khoá `builder: true`): chủ catalog xác nhận vị trí là vùng nội dung rộng,
         * hợp với khối HTML đầy đủ (không nằm trong `<head>`, form hay thẻ sản phẩm). Chỉ có nghĩa với hook `slot`.
         */
        public bool $builder = false,
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
        return new self($name, $this->type, $this->public, $this->since, $this->args, $this->description, $this->stability, $this->onError, $this->deprecated, $this->transaction, $this->builder);
    }

    /**
     * @param  array{type: string, visibility?: string, since?: string, args?: array<string, string>, description?: string, stability?: string, on_error?: string, deprecated?: string, transaction?: bool, builder?: bool}  $definition
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
            deprecated: isset($definition['deprecated']) ? (string) $definition['deprecated'] : null,
            transaction: (bool) ($definition['transaction'] ?? false),
            builder: (bool) ($definition['builder'] ?? false),
        );
    }
}
