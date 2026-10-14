<?php

declare(strict_types=1);

namespace Plugin\TrackingPixels\Infrastructure;

use Modules\Tenancy\Contracts\Settings;
use Plugin\TrackingPixels\TrackingPixelsServiceProvider;

/**
 * Cấu hình của plugin (Admin → Cấu hình → Theo dõi quảng cáo).
 */
final class TrackingSettings
{
    /** Cookie ghi lựa chọn đồng ý của khách (thanh đồng ý hoặc biểu thức tuỳ chỉnh): `granted` / `denied`. */
    public const CONSENT_COOKIE = 'vani_consent';

    public function __construct(private readonly Settings $settings) {}

    public function get(string $key): mixed
    {
        return $this->settings->get(TrackingPixelsServiceProvider::ID, $key);
    }

    public function metaPixelId(): ?string
    {
        return $this->filled('meta_pixel_id');
    }

    public function ga4Id(): ?string
    {
        return $this->filled('ga4_measurement_id');
    }

    public function gtmId(): ?string
    {
        return $this->filled('gtm_container_id');
    }

    public function anyConfigured(): bool
    {
        return $this->metaPixelId() !== null || $this->ga4Id() !== null || $this->gtmId() !== null;
    }

    public function consentMode(): string
    {
        return (string) ($this->get('consent_mode') ?? 'banner');
    }

    public function metaServerEnabled(): bool
    {
        return (bool) $this->get('meta_capi_enabled') && $this->metaPixelId() !== null && $this->filled('meta_access_token') !== null;
    }

    public function ga4ServerEnabled(): bool
    {
        return (bool) $this->get('ga4_mp_enabled') && $this->ga4Id() !== null && $this->filled('ga4_api_secret') !== null;
    }

    public function debug(): bool
    {
        return (bool) $this->get('debug');
    }

    public function filled(string $key): ?string
    {
        $value = trim((string) $this->get($key));

        return $value === '' ? null : $value;
    }
}
