<?php

declare(strict_types=1);

namespace Plugin\VietQr;

use Modules\Extension\PluginServiceProvider;
use Modules\Payment\Contracts\PaymentGateway;
use Plugin\VietQr\Infrastructure\VietQrGateway;

final class VietQrServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'vani.vietqr';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/vietqr.php'), 'vani.vietqr');

        $this->app->singleton(VietQrGateway::class, fn (): VietQrGateway => new VietQrGateway(
            accounts: (array) config('vani.vietqr.accounts', []),
            secret: (string) config('vani.vietqr.secret'),
            ttlSeconds: (int) config('vani.vietqr.ttl', 900),
        ));
    }

    public function boot(): void
    {
        $this->contribute(PaymentGateway::TAG, VietQrGateway::class);
    }
}
