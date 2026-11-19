<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Api;

use Modules\Shared\Domain\BusinessRuleViolation;

final class IntegrationRequestRejected extends BusinessRuleViolation
{
    /**
     * @param  array<string, mixed>  $details
     */
    private function __construct(string $message, private readonly string $errorCode, private readonly int $httpStatus, private readonly array $details = [])
    {
        parent::__construct($message);
    }

    public static function orderNotFound(string $number): self
    {
        return new self(__('integration::texts.integration_request_rejected.order_not_found'), 'integration.order_not_found', 404, ['number' => $number]);
    }

    public static function idempotencyKeyRequired(): self
    {
        return new self(__('integration::texts.integration_request_rejected.idempotency_key_header_required_8'), 'integration.idempotency_key_required', 400);
    }

    public static function referenceConflict(string $number, string $existing): self
    {
        return new self(__('integration::texts.integration_request_rejected.order_was_already_confirmed_different'), 'integration.reference_conflict', 409, ['number' => $number, 'external_id' => $existing]);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->httpStatus;
    }

    public function details(): array
    {
        return $this->details;
    }
}
