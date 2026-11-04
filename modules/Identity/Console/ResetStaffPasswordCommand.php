<?php

declare(strict_types=1);

namespace Modules\Identity\Console;

use Illuminate\Console\Command;
use Modules\Identity\Application\StaffPasswords;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * In link đặt lại mật khẩu cho nhân viên (reference-parity §3.3-5) — khi nhân viên không nhận được email hoặc khôi phục
 * Owner. Không in, không đặt mật khẩu hộ. Link một lần, hạn như link quên mật khẩu.
 */
final class ResetStaffPasswordCommand extends Command
{
    protected $signature = 'vani:staff:reset-password {email}';

    protected $description = 'In link đặt lại mật khẩu (hoặc lời mời mới nếu chưa kích hoạt) cho nhân viên Admin';

    public function handle(StaffPasswords $passwords, CurrentContext $context): int
    {
        $staff = StaffUser::query()->where('email', (string) $this->argument('email'))->first();
        if ($staff === null) {
            $this->error('Không có nhân viên với email này.');

            return self::FAILURE;
        }

        $link = $context->runAs(ContextScope::system('console'), fn (): ?string => $passwords->issueLink($staff));
        if ($link === null) {
            $this->error('Nhân viên đang bị khoá: mở khoá trong Admin → Nhân viên trước.');

            return self::FAILURE;
        }

        $this->line($link);

        return self::SUCCESS;
    }
}
