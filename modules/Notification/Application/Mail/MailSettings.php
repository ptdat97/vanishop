<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Mail;

use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

/**
 * Cấu hình email gửi đi (Admin → Cài đặt → Email gửi đi): cách gửi (theo máy chủ `.env` hoặc SMTP của cửa hàng) và
 * người gửi. Đọc lúc gửi (SettingsMailTransport) — đổi cấu hình có hiệu lực ngay, kể cả queue worker đang chạy.
 */
final class MailSettings
{
    public const TRANSPORT = 'mail.transport';

    public const HOST = 'mail.smtp_host';

    public const PORT = 'mail.smtp_port';

    public const ENCRYPTION = 'mail.smtp_encryption';

    public const USERNAME = 'mail.smtp_username';

    public const PASSWORD = 'mail.smtp_password';

    public const FROM_ADDRESS = 'mail.from_address';

    public const FROM_NAME = 'mail.from_name';

    public const SERVER = 'server';

    public const SMTP = 'smtp';

    public function __construct(private readonly Settings $settings) {}

    public static function define(Settings $settings): void
    {
        $text = fn (string $key): string => "notification::texts.mail_settings.{$key}";
        $mail = SettingGroup::Mail;
        foreach ([
            new SettingDefinition('core', self::TRANSPORT, $text('transport'), 'select', self::SERVER, options: [self::SERVER => $text('transport_server'), self::SMTP => $text('transport_smtp')], help: $text('transport_help'), group: $mail),
            new SettingDefinition('core', self::HOST, $text('host'), help: $text('host_help'), rules: ['max:255'], group: $mail),
            new SettingDefinition('core', self::PORT, $text('port'), 'int', 587, help: $text('port_help'), rules: ['min:1', 'max:65535'], group: $mail),
            new SettingDefinition('core', self::ENCRYPTION, $text('encryption'), 'select', 'tls', options: ['tls' => $text('encryption_tls'), 'ssl' => $text('encryption_ssl'), 'none' => $text('encryption_none')], group: $mail),
            new SettingDefinition('core', self::USERNAME, $text('username'), rules: ['max:255'], group: $mail),
            new SettingDefinition('core', self::PASSWORD, $text('password'), 'secret', help: $text('password_help'), group: $mail),
            new SettingDefinition('core', self::FROM_ADDRESS, $text('from_address'), help: $text('from_address_help'), rules: ['email'], group: $mail),
            new SettingDefinition('core', self::FROM_NAME, $text('from_name'), help: $text('from_name_help'), rules: ['max:120'], group: $mail),
        ] as $definition) {
            $settings->define($definition);
        }
    }

    /**
     * SMTP của cửa hàng khi đã chọn SMTP và có máy chủ; null = gửi theo cấu hình máy chủ (`.env`).
     *
     * @return array{host: string, port: int, encryption: string, username: string, password: string}|null
     */
    public function smtp(): ?array
    {
        $host = trim((string) $this->settings->get('core', self::HOST));
        if ($this->settings->get('core', self::TRANSPORT) !== self::SMTP || $host === '') {
            return null;
        }

        return [
            'host' => $host,
            'port' => (int) $this->settings->get('core', self::PORT, 587),
            'encryption' => (string) $this->settings->get('core', self::ENCRYPTION, 'tls'),
            'username' => (string) $this->settings->get('core', self::USERNAME),
            'password' => (string) $this->settings->get('core', self::PASSWORD),
        ];
    }

    /**
     * Người gửi đặt trong Admin; null = theo máy chủ (`MAIL_FROM_*`).
     *
     * @return array{address: string, name: string}|null
     */
    public function from(): ?array
    {
        $address = trim((string) $this->settings->get('core', self::FROM_ADDRESS));
        if ($address === '') {
            return null;
        }
        $name = trim((string) $this->settings->get('core', self::FROM_NAME));

        return ['address' => $address, 'name' => $name !== '' ? $name : (string) config('mail.from.name')];
    }
}
