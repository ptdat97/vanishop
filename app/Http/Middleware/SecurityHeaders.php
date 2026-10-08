<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Support\AdminPath;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header bảo mật cho mọi response (go-live gate, security §): chống MIME sniffing, nhúng iframe (clickjacking), lộ URL
 * qua Referer, tắt quyền trình duyệt không dùng; HSTS khi chạy HTTPS ở production. Header đã đặt (vd. bởi plugin,
 * CDN) không bị ghi đè. CSP chưa bật: layout có style/JSON-LD inline — xem docs/15-security/security.md.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $defaults = [
            'X-Content-Type-Options' => 'nosniff',
            // Admin không bao giờ được nhúng; storefront cho phép cùng origin (vd. xem trước trong Admin).
            'X-Frame-Options' => AdminPath::matches($request) ? 'DENY' : 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        ];
        if ($request->isSecure() && app()->isProduction()) {
            $defaults['Strict-Transport-Security'] = 'max-age='.(int) config('vanishop.security.hsts_max_age', 31536000).'; includeSubDomains';
        }
        foreach ($defaults as $name => $value) {
            if (! $headers->has($name)) {
                $headers->set($name, $value);
            }
        }

        return $response;
    }
}
