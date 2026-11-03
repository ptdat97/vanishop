<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Persistence\Models\StaffUser;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Link một lần cho nhân viên (reference-parity §3.3): quên mật khẩu (broker `staff`, 60 phút, chỉ nhân viên đang hoạt
 * động) và lời mời đặt mật khẩu lần đầu (broker `staff_invites`, 24 giờ, chỉ nhân viên `invited`). Admin không bao giờ
 * đặt mật khẩu hộ nhân viên.
 */
final class StaffPasswords
{
    public const RESET = 'staff';

    public const INVITE = 'staff_invites';

    public function __construct(
        private readonly PasswordBrokerFactory $brokers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Không báo email có tồn tại hay không: email lạ, nhân viên bị khoá/chưa nhận lời mời, gửi lại quá nhanh → im lặng.
     */
    public function sendReset(string $email): void
    {
        $staff = StaffUser::query()->where('email', $email)->first();
        if ($staff === null || ! $staff->isActive() || $this->broker(self::RESET)->getRepository()->recentlyCreatedToken($staff)) {
            return;
        }

        $token = $this->broker(self::RESET)->createToken($staff);
        $this->mail($staff, __('identity::staff.reset_subject'), __('identity::staff.reset_body', [
            'name' => $staff->name,
            'url' => route('admin.password.reset', ['token' => $token, 'email' => $staff->email]),
            'minutes' => (int) config('auth.passwords.'.self::RESET.'.expire'),
        ]));
        $this->audit->record('identity.staff.password_reset_requested', 'staff_user', $staff->id);
    }

    public function sendInvite(StaffUser $staff): void
    {
        $token = $this->broker(self::INVITE)->createToken($staff);
        $this->mail($staff, __('identity::staff.invite_subject', ['store' => config('app.name')]), __('identity::staff.invite_body', [
            'name' => $staff->name,
            'store' => config('app.name'),
            'url' => route('admin.invitation.show', ['token' => $token, 'email' => $staff->email]),
            'hours' => intdiv((int) config('auth.passwords.'.self::INVITE.'.expire'), 60),
        ]));
    }

    /**
     * Đặt mật khẩu bằng link. Lời mời → nhân viên chuyển sang `active`. Sai/hết hạn token, sai trạng thái → false.
     */
    public function complete(string $broker, string $email, string $token, string $password): bool
    {
        $required = $broker === self::INVITE ? StaffUser::STATUS_INVITED : StaffUser::STATUS_ACTIVE;
        $staff = StaffUser::query()->where('email', $email)->first();
        if ($staff === null || $staff->status !== $required) {
            return false;
        }

        $status = $this->broker($broker)->reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function (StaffUser $staff, string $password): void {
                $staff->forceFill(['password' => $password, 'status' => StaffUser::STATUS_ACTIVE, 'remember_token' => Str::random(60)])->save();
            },
        );
        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return false;
        }

        $this->audit->record($broker === self::INVITE ? 'identity.staff.invitation_accepted' : 'identity.staff.password_reset', 'staff_user', $staff->id);

        return true;
    }

    /** Huỷ link còn hạn (khoá nhân viên). */
    public function revoke(StaffUser $staff): void
    {
        $this->broker(self::RESET)->deleteToken($staff);
        $this->broker(self::INVITE)->deleteToken($staff);
    }

    private function broker(string $name): PasswordBroker
    {
        /** @var PasswordBroker */
        return $this->brokers->broker($name);
    }

    private function mail(StaffUser $staff, string $subject, string $body): void
    {
        try {
            Mail::raw($body, function (Message $message) use ($staff, $subject): void {
                $message->to($staff->email, $staff->name)->subject($subject);
            });
        } catch (TransportExceptionInterface $exception) {
            // Link vẫn hợp lệ; nhân viên quản trị gửi lại lời mời hoặc nhân viên yêu cầu lại.
            Log::error('Không gửi được email cho nhân viên.', ['staff_id' => $staff->id, 'error' => $exception->getMessage()]);
        }
    }
}
