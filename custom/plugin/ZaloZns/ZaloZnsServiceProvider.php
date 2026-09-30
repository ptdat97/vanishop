<?php

declare(strict_types=1);

namespace Plugin\ZaloZns;

use Modules\Customer\Contracts\OtpSender;
use Modules\Extension\PluginServiceProvider;
use Modules\Notification\Contracts\NotificationChannel;
use Plugin\ZaloZns\Infrastructure\ZnsChannel;
use Plugin\ZaloZns\Infrastructure\ZnsClient;
use Plugin\ZaloZns\Infrastructure\ZnsOtpSender;

final class ZaloZnsServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'vani.zalo-zns';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/zalo-zns.php'), 'vani.zalo-zns');

        $this->app->singleton(ZnsClient::class, fn ($app): ZnsClient => new ZnsClient(
            $app->make('cache')->store(config('vani.zalo-zns.cache_store')),
            (string) config('vani.zalo-zns.api_base'),
            (string) config('vani.zalo-zns.oauth_base'),
            (string) config('vani.zalo-zns.app_id'),
            (string) config('vani.zalo-zns.app_secret'),
            (string) config('vani.zalo-zns.refresh_token'),
            (string) config('vani.zalo-zns.mode', 'production'),
        ));
        $this->app->bind(ZnsOtpSender::class, fn ($app): ZnsOtpSender => new ZnsOtpSender(
            $app->make(ZnsClient::class),
            (string) config('vani.zalo-zns.otp_template_id'),
            (string) config('vani.zalo-zns.otp_param', 'otp'),
        ));
    }

    public function boot(): void
    {
        $this->contribute(NotificationChannel::TAG, ZnsChannel::class);
        $this->contribute(OtpSender::TAG, ZnsOtpSender::class);
    }
}
