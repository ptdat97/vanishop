<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Modules\Shared\Support\LocalizedUrls;

/**
 * Email của luồng đăng ký/mật khẩu bằng email (Core 0.5.12). Link trỏ về trang của storefront native; frontend headless
 * đặt `vanishop.customer.email_auth.verify_url|reset_url` (có `{token}`) để link về app riêng.
 */
final class AccountEmails
{
    public function sendVerification(string $email, ?string $fullName, string $token, int $ttlHours): void
    {
        $url = $this->url('verify_url', 'storefront.account.register.verify', $token);
        $body = __('customer::messages.verify_email_body', ['name' => $fullName ?? $email, 'url' => $url, 'hours' => $ttlHours]);

        Mail::raw($body, function (Message $message) use ($email, $fullName): void {
            $message->to($email, $fullName)->subject(__('customer::messages.verify_email_subject'));
        });
    }

    /** Xác minh email của tài khoản đang có (đổi/thêm email trong hồ sơ — 0.5.32). */
    public function sendEmailChangeVerification(string $email, ?string $fullName, string $token, int $ttlHours): void
    {
        $url = $this->url('verify_email_url', 'storefront.account.email.verify', $token);
        $body = __('customer::messages.verify_email_change_body', ['name' => $fullName ?? $email, 'url' => $url, 'hours' => $ttlHours]);

        Mail::raw($body, function (Message $message) use ($email, $fullName): void {
            $message->to($email, $fullName)->subject(__('customer::messages.verify_email_change_subject'));
        });
    }

    public function sendPasswordReset(string $email, ?string $fullName, string $token, int $ttlMinutes): void
    {
        $url = $this->url('reset_url', 'storefront.account.password.reset', $token);
        $body = __('customer::messages.reset_password_body', ['name' => $fullName ?? $email, 'url' => $url, 'minutes' => $ttlMinutes]);

        Mail::raw($body, function (Message $message) use ($email, $fullName): void {
            $message->to($email, $fullName)->subject(__('customer::messages.reset_password_subject'));
        });
    }

    private function url(string $configKey, string $route, string $token): string
    {
        $template = (string) config("vanishop.customer.email_auth.{$configKey}", '');

        return $template !== '' ? str_replace('{token}', rawurlencode($token), $template) : LocalizedUrls::route($route, ['token' => $token]);
    }
}
