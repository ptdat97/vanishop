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
        return new self('Không tìm thấy đơn.', 'integration.order_not_found', 404, ['number' => $number]);
    }

    public static function referenceConflict(string $number, string $existing): self
    {
        return new self('Đơn đã được xác nhận với số chứng từ khác.', 'integration.reference_conflict', 409, ['number' => $number, 'external_id' => $existing]);
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
