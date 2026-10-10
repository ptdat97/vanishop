<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Modules\Shared\Contracts\AlertChannel;
use Modules\Shared\Contracts\Data\Alert;
use Modules\Shared\Support\StoreClock;

/**
 * Kênh cảnh báo mặc định của Core: email tới VANI_ALERT_EMAILS, gửi ngay (không qua queue). Danh sách trống → bỏ qua.
 */
final class MailAlertChannel implements AlertChannel
{
    public function code(): string
    {
        return 'mail';
    }

    public function send(Alert $alert): void
    {
        $recipients = (array) config('vanishop.alerts.mail_to');
        if ($recipients === []) {
            return;
        }

        $body = implode("\n", [
            $alert->title,
            '',
            $alert->detail,
            '',
            'Mức độ: '.$alert->severity.' · Mã: '.$alert->key,
            'Bắt đầu: '.StoreClock::format($alert->since).' ('.StoreClock::timezone().')',
            'Website: '.config('app.url'),
        ]);

        Mail::raw($body, fn (Message $message) => $message->to($recipients)->subject('['.config('app.name').'] '.$alert->subject()));
    }
}
