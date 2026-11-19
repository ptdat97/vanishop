<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Modules\Identity\Contracts\Data\ScopeRef;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Admin → Cài đặt → Email gửi đi → "Gửi email thử": gửi ngay (không qua hàng đợi) tới email của nhân viên đang đăng
 * nhập bằng cấu hình đã lưu; lỗi kết nối/đăng nhập SMTP báo lại nguyên văn để chủ shop sửa.
 */
final class MailTestController
{
    public function __invoke(Request $request): RedirectResponse
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        $email = (string) $request->user('staff')?->email;
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return back()->with('error', __('notification::texts.mail_settings.test_no_email'));
        }

        try {
            Mail::raw(__('notification::texts.mail_settings.test_body'), function (Message $message) use ($email): void {
                $message->to($email)->subject(__('notification::texts.mail_settings.test_subject', ['store' => (string) config('app.name')]));
            });
        } catch (TransportExceptionInterface $exception) {
            return back()->with('error', __('notification::texts.mail_settings.test_failed', ['error' => $exception->getMessage()]));
        }

        return back()->with('success', __('notification::texts.mail_settings.test_sent', ['email' => $email]));
    }
}
