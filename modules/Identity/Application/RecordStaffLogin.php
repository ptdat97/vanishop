<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Facades\Log;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Ghi nhận đăng nhập Admin: cập nhật last_login_at, audit, và cảnh báo khi đăng nhập từ IP chưa từng dùng
 * (kiểm soát bù trừ khi không có 2FA — ADR-020).
 */
final class RecordStaffLogin
{
    public const ACTION_LOGIN = 'identity.staff.login';

    public const ACTION_NEW_IP = 'identity.staff.login_new_ip';

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CurrentContext $context,
    ) {}

    public function handle(StaffUser $staff, ?string $ip): void
    {
        $knownIp = AuditLog::query()
            ->where('actor_type', 'staff')
            ->where('actor_id', $staff->id)
            ->where('action', self::ACTION_LOGIN)
            ->where('ip_address', $ip)
            ->exists();
        $isFirstLogin = $staff->last_login_at === null;

        $staff->forceFill(['last_login_at' => now()])->save();

        $this->context->runAs(new ContextScope(Actor::staff($staff->id, $staff->email)), function () use ($staff, $ip, $knownIp, $isFirstLogin): void {
            $this->audit->record(self::ACTION_LOGIN, 'staff_user', $staff->id);

            if (! $knownIp && ! $isFirstLogin) {
                $this->audit->record(self::ACTION_NEW_IP, 'staff_user', $staff->id, ['ip' => $ip]);
                Log::warning('Nhân viên đăng nhập Admin từ IP mới.', ['staff_id' => $staff->id, 'ip' => $ip]);
            }
        });
    }
}
