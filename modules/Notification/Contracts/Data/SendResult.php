<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts\Data;

final readonly class SendResult
{
    private function __construct(
        public string $kind,
        public ?string $providerMessageId = null,
        public ?string $error = null,
    ) {}

    public static function sent(?string $providerMessageId = null): self
    {
        return new self('sent', $providerMessageId);
    }

    public static function retryable(string $error): self
    {
        return new self('retryable', error: $error);
    }

    public static function permanent(string $error): self
    {
        return new self('permanent', error: $error);
    }

    public function isSent(): bool
    {
        return $this->kind === 'sent';
    }

    public function isRetryable(): bool
    {
        return $this->kind === 'retryable';
    }
}
