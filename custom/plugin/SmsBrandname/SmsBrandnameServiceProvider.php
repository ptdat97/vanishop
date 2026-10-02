<?php

declare(strict_types=1);

namespace Plugin\SmsBrandname;

use Modules\Customer\Contracts\OtpSender;
use Modules\Extension\PluginServiceProvider;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Tenancy\Contracts\Settings;
use Plugin\SmsBrandname\Infrastructure\EsmsClient;
use Plugin\SmsBrandname\Infrastructure\SmsChannel;
use Plugin\SmsBrandname\Infrastructure\SmsOtpSender;

final class SmsBrandnameServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.sms-brandname';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/sms-brandname.php'), 'vani.sms-brandname');

        $this->app->bind(EsmsClient::class, fn (): EsmsClient => new EsmsClient(
            (string) config('vani.sms-brandname.api_base'),
            (string) config('vani.sms-brandname.api_key'),
            (string) config('vani.sms-brandname.secret_key'),
            (bool) config('vani.sms-brandname.sandbox', false),
        ));
        $this->app->bind(SmsChannel::class, fn ($app): SmsChannel => new SmsChannel(
            $app->make(EsmsClient::class),
            $app->make(Settings::class),
            (string) config('vani.sms-brandname.brandname'),
        ));
        $this->app->bind(SmsOtpSender::class, fn ($app): SmsOtpSender => new SmsOtpSender(
            $app->make(EsmsClient::class),
            $app->make(SmsChannel::class),
            (string) config('vani.sms-brandname.otp_message'),
            (bool) config('vani.sms-brandname.otp_enabled', true),
        ));
    }

    public function boot(): void
    {
        $this->settings([
            ['key' => 'brandname', 'label' => 'Brandname SMS', 'help' => 'Brandname đã đăng ký với nhà mạng.'],
        ]);

        $this->contribute(NotificationChannel::TAG, SmsChannel::class);
        $this->contribute(OtpSender::TAG, SmsOtpSender::class);
    }
}
