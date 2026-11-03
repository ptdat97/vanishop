<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Support\Carbon;
use Modules\Tenancy\Contracts\Settings;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Chế độ bảo trì storefront (`core` / `storefront.maintenance`, reference-parity §3.2). Nhân viên xem trước bằng
 * cookie có chữ ký phát từ Admin: cookie phiên Admin giới hạn path `/{admin}` (ADR-020) nên storefront không đọc được.
 */
final class Maintenance
{
    public const COOKIE = 'vani_maintenance_bypass';

    public const BYPASS_HOURS = 12;

    public function __construct(private readonly Settings $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('core', 'storefront.maintenance', false);
    }

    public function message(): string
    {
        return trim((string) $this->settings->get('core', 'storefront.maintenance_message', ''));
    }

    public function bypassCookie(): Cookie
    {
        $expiresAt = Carbon::now()->addHours(self::BYPASS_HOURS)->getTimestamp();
        $value = $expiresAt.'.'.$this->mac((string) $expiresAt);

        return new Cookie(self::COOKIE, $value, $expiresAt, '/', config('session.domain'), (bool) config('session.secure'), true, false, Cookie::SAMESITE_LAX);
    }

    public function bypassValid(?string $cookie): bool
    {
        [$expiresAt, $mac] = array_pad(explode('.', (string) $cookie, 2), 2, '');

        return ctype_digit($expiresAt) && (int) $expiresAt >= Carbon::now()->getTimestamp() && hash_equals($this->mac($expiresAt), $mac);
    }

    private function mac(string $expiresAt): string
    {
        return hash_hmac('sha256', 'vani.maintenance-bypass|'.$expiresAt, (string) config('app.key'));
    }
}
