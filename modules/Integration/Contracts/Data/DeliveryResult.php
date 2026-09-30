<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts\Data;

/**
 * Kết quả gửi/xử lý một message: ok | retryable (thử lại theo backoff) | permanent (không thử lại)
 * | stale (inbox: bản cũ hơn dữ liệu hiện có, bỏ qua).
 */
final readonly class DeliveryResult
{
    private const OK = 'ok';

    private const RETRYABLE = 'retryable';

    private const PERMANENT = 'permanent';

    private const STALE = 'stale';

    private function __construct(
        public string $kind,
        public ?string $error = null,
        /** Định danh phía nhận (số chứng từ ERP…) — lưu vào external_references nếu có. */
        public ?string $externalId = null,
    ) {}

    public static function ok(?string $externalId = null): self
    {
        return new self(self::OK, externalId: $externalId);
    }

    public static function retryable(string $error): self
    {
        return new self(self::RETRYABLE, $error);
    }

    public static function permanent(string $error): self
    {
        return new self(self::PERMANENT, $error);
    }

    public static function stale(string $reason = 'stale'): self
    {
        return new self(self::STALE, $reason);
    }

    public function isOk(): bool
    {
        return $this->kind === self::OK;
    }

    public function isRetryable(): bool
    {
        return $this->kind === self::RETRYABLE;
    }

    public function isPermanent(): bool
    {
        return $this->kind === self::PERMANENT;
    }

    public function isStale(): bool
    {
        return $this->kind === self::STALE;
    }
}
