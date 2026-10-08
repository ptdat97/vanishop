<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Modules\Shared\Http\SessionlessRoutes;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trang công khai cache được ở CDN (roadmap Phase 7, storefront §5): route dùng middleware này chạy KHÔNG phiên
 * (bỏ SESSIONLESS khỏi nhóm `web`) nên HTML giống nhau với mọi khách, không `Set-Cookie`, không ghi phiên mỗi lượt xem.
 * Phần riêng của khách do JS lấy từ `GET /_vani/phien`.
 *
 * Không cache: link xem trước có chữ ký (`signature`), phản hồi khác 200.
 */
final class PublicPageCache
{
    public const ATTRIBUTE = 'vani.page_cache';

    /** Middleware bỏ khỏi route trang công khai. */
    public const SESSIONLESS = SessionlessRoutes::MIDDLEWARE;

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::ATTRIBUTE, true);
        View::share('errors', new ViewErrorBag);

        $response = $next($request);

        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
        }

        $config = (array) config('vanishop.storefront.page_cache');
        if (! (bool) ($config['enabled'] ?? true) || $request->query->has('signature') || $response->getStatusCode() !== 200 || ! $request->isMethodCacheable()) {
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }

        $response->headers->set('Cache-Control', sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', (int) ($config['s_maxage'] ?? 300), (int) ($config['stale_while_revalidate'] ?? 600)));
        $response->headers->set('Vary', 'Accept-Encoding, X-Vani-Locale');

        return $response;
    }
}
