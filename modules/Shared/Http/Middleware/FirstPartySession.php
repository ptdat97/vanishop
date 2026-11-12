<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storefront API nhận phiên web của chính website (ADR-044 §4): request CÙNG NGUỒN (trình duyệt báo
 * `Sec-Fetch-Site: same-origin`, hoặc `Origin` trùng host) và không mang `Authorization` thì chạy phiên web như trang Blade
 * — JS của theme gọi API mà không phải giữ token trong JavaScript. Request ghi (POST/PUT/PATCH/DELETE) phải kèm token CSRF
 * của phiên (`X-CSRF-TOKEN`/`_token`). Client khác (app, headless, đối tác) vẫn dùng Bearer/cart token như cũ; request khác
 * nguồn không bao giờ mở phiên. Đặt ĐẦU nhóm route Storefront API, trước middleware nhận diện khách.
 */
final class FirstPartySession
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->headers->has('Authorization') || ! self::sameOrigin($request)) {
            return $next($request);
        }

        $pipes = [EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class];
        if (! $request->isMethodSafe()) {
            $pipes[] = ValidateCsrfToken::class;
        }

        return (new Pipeline($this->container))->send($request)->through($pipes)->then($next);
    }

    public static function sameOrigin(Request $request): bool
    {
        $site = (string) $request->headers->get('Sec-Fetch-Site', '');
        if ($site !== '') {
            return $site === 'same-origin';
        }
        $origin = (string) $request->headers->get('Origin', '');

        return $origin !== '' && parse_url($origin, PHP_URL_HOST) === $request->getHost();
    }
}
