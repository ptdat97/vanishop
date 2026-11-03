<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Thao tác quản trị nhân viên/vai trò bị chặn để Admin không tự khoá mình hoặc mất quyền Owner cuối cùng.
 */
final class StaffRejected extends BusinessRuleViolation
{
    private function __construct(string $message, private readonly string $errorCode)
    {
        parent::__construct($message);
    }

    public static function self(): self
    {
        return new self(__('identity::staff.cannot_change_self'), 'identity.staff_self');
    }

    public static function lastOwner(): self
    {
        return new self(__('identity::staff.last_owner'), 'identity.last_owner');
    }

    public static function exceedsOwnPermissions(): self
    {
        return new self(__('identity::staff.exceeds_own_permissions'), 'identity.exceeds_own_permissions');
    }

    public static function roleInUse(): self
    {
        return new self(__('identity::staff.role_in_use'), 'identity.role_in_use');
    }

    public static function systemRole(): self
    {
        return new self(__('identity::staff.system_role'), 'identity.system_role');
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return 422;
    }
}
