<?php

declare(strict_types=1);

namespace Plugin\SmsBrandname\Infrastructure;

use Modules\Extension\Contracts\Data\HealthStatus;
use Modules\Extension\Contracts\PluginHealthCheck;
use Modules\Tenancy\Contracts\Settings;
use Plugin\SmsBrandname\SmsBrandnameServiceProvider;

/**
 * Kiểm tra cấu hình eSMS (không gọi mạng): thiếu khoá → không gửi được SMS/OTP; sandbox → tin không tới khách.
 */
final class SmsHealthCheck implements PluginHealthCheck
{
    public function __construct(private readonly Settings $settings) {}

    public function check(): HealthStatus
    {
        if ((string) config('vani.sms-brandname.api_key') === '' || (string) config('vani.sms-brandname.secret_key') === '') {
            return HealthStatus::error('Chưa cấu hình ESMS_API_KEY/ESMS_SECRET_KEY — không gửi được SMS và OTP.');
        }
        if ((string) ($this->settings->get(SmsBrandnameServiceProvider::ID, 'brandname') ?? config('vani.sms-brandname.brandname')) === '') {
            return HealthStatus::warning('Chưa có brandname — tin gửi bằng đầu số mặc định của eSMS.');
        }
        if ((bool) config('vani.sms-brandname.sandbox', false)) {
            return HealthStatus::warning('Đang ở chế độ sandbox (ESMS_SANDBOX) — tin không tới khách.');
        }

        return HealthStatus::ok();
    }
}
