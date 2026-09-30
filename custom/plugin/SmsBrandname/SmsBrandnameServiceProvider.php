<?php

declare(strict_types=1);

namespace Plugin\SmsBrandname;

use Modules\Brand\Contracts\BrandDirectory;
use Modules\Customer\Contracts\OtpSender;
use Modules\Extension\PluginServiceProvider;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Shared\Context\CurrentContext;
use Plugin\SmsBrandname\Infrastructure\EsmsClient;
use Plugin\SmsBrandname\Infrastructure\SmsChannel;
use Plugin\SmsBrandname\Infrastructure\SmsOtpSender;

final class SmsBrandnameServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'vani.sms-brandname';
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
            $app->make(BrandDirectory::class),
            (string) config('vani.sms-brandname.brandname'),
            (array) config('vani.sms-brandname.brandnames', []),
        ));
        $this->app->bind(SmsOtpSender::class, fn ($app): SmsOtpSender => new SmsOtpSender(
            $app->make(EsmsClient::class),
            $app->make(SmsChannel::class),
            $app->make(CurrentContext::class),
            (string) config('vani.sms-brandname.otp_message'),
            (bool) config('vani.sms-brandname.otp_enabled', true),
        ));
    }

    public function boot(): void
    {
        $this->contribute(NotificationChannel::TAG, SmsChannel::class);
        $this->contribute(OtpSender::TAG, SmsOtpSender::class);
    }
}
