<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Mail;

use Illuminate\Contracts\Container\Container;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Mailer mặc định `vani` (config/mail.php): mỗi lần gửi đọc Admin → Cài đặt → Email gửi đi — SMTP của cửa hàng hoặc
 * mailer theo máy chủ (`fallback` = MAIL_MAILER), và người gửi nếu đã đặt. Đọc lúc gửi chứ không lúc boot: worker chạy
 * lâu vẫn theo cấu hình mới (Settings nạp lại mỗi job).
 */
final class SettingsMailTransport implements TransportInterface
{
    private ?string $smtpKey = null;

    private ?EsmtpTransport $smtp = null;

    public function __construct(private readonly Container $app, private readonly string $fallback) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $settings = $this->app->make(MailSettings::class);
        $from = $settings->from();
        if ($from !== null && $message instanceof Email) {
            $message->from(new Address($from['address'], $from['name']));
            $envelope = Envelope::create($message);
        }

        return $this->transport($settings)->send($message, $envelope);
    }

    public function __toString(): string
    {
        return 'vani';
    }

    private function transport(MailSettings $settings): TransportInterface
    {
        $smtp = $settings->smtp();
        if ($smtp === null) {
            return $this->app->make(MailManager::class)->mailer($this->fallback === 'vani' ? 'log' : $this->fallback)->getSymfonyTransport();
        }

        $key = hash('sha256', serialize($smtp));
        if ($this->smtp === null || $this->smtpKey !== $key) {
            // ssl = SSL/TLS ngay từ đầu (cổng 465); tls = STARTTLS (587); none = không mã hoá.
            $transport = new EsmtpTransport($smtp['host'], $smtp['port'], $smtp['encryption'] === 'ssl');
            $transport->setAutoTls($smtp['encryption'] !== 'none');
            $transport->setRequireTls($smtp['encryption'] === 'tls');
            if ($smtp['username'] !== '') {
                $transport->setUsername($smtp['username']);
                $transport->setPassword($smtp['password']);
            }
            $this->smtp = $transport;
            $this->smtpKey = $key;
        }

        return $this->smtp;
    }
}
