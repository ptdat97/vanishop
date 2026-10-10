<?php

declare(strict_types=1);

namespace Plugin\PhoneVn;

use Modules\Extension\PluginServiceProvider;
use Modules\Shared\Contracts\PhoneNumberPolicy;
use Plugin\PhoneVn\Infrastructure\VietnamPhonePolicy;

/**
 * Plugin hệ thống: luật số điện thoại Việt Nam (PhoneNumberPolicy `vn`) — nhận 0912…, 84…, +84…, số bàn 02…; chuẩn hoá
 * về +84, hiển thị dạng 0….
 */
final class PhoneVnServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.phone-vn';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->contribute(PhoneNumberPolicy::TAG, VietnamPhonePolicy::class);
    }
}
