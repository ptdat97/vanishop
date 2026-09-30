<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Lỗi xác thực/phân quyền của Integration API: {"error": {"code": "integration.*"}}.
 */
final class IntegrationAccessDenied extends BusinessRuleViolation
{
    private function __construct(string $message, private readonly string $errorCode, private readonly int $httpStatus)
    {
        parent::__construct($message);
    }

    public static function unauthenticated(): self
    {
        return new self('Thiếu hoặc sai chữ ký Integration API.', 'integration.unauthenticated', 401);
    }

    public static function ipNotAllowed(): self
    {
        return new self('Địa chỉ IP không được phép.', 'integration.ip_not_allowed', 403);
    }

    public static function insufficientScope(string $scope): self
    {
        return new self("Client thiếu scope {$scope}.", 'integration.insufficient_scope', 403);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->httpStatus;
    }
}
