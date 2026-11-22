<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Shared\Http\SessionlessRoutes;
use Modules\Storefront\Contracts\PublicPageExpiry;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trang công khai (trang chủ, danh mục, thương hiệu, tìm kiếm, sản phẩm, trang plugin `cacheable`) — storefront §5.
 * Route bỏ SESSIONLESS khỏi nhóm `web`; middleware này quyết định theo `vanishop.storefront.page_cache.enabled`:
 *
 * - **Tắt (mặc định, không CDN)**: chạy lại phiên + nhận diện khách như mọi trang khác → HTML render sẵn phần riêng của
 *   khách (đăng nhập, giỏ, thông báo, giá thành viên), `Cache-Control: private, no-store`.
 * - **Bật (tuỳ chọn nâng cao khi deploy có CDN)**: không phiên, HTML giống nhau với mọi khách, không `Set-Cookie`,
 *   `Cache-Control: public, s-maxage`; phần riêng của khách do JS lấy từ `GET /_vani/phien`. Không cache: link xem trước
 *   có chữ ký (`signature`), phản hồi khác 200.
 */
final class PublicPageCache
{
    /** Có mặt khi trang đang chạy chế độ CDN (không phiên). */
    public const ATTRIBUTE = 'vani.page_cache';

    /** Có mặt trên mọi trang công khai, cả khi không dùng CDN (SEO: canonical, hreflang). */
    public const PUBLIC_PAGE = 'vani.public_page';

    /** Middleware bỏ khỏi route trang công khai. */
    public const SESSIONLESS = SessionlessRoutes::MIDDLEWARE;

    public function __construct(private readonly Router $router) {}

    public static function enabled(): bool
    {
        return (bool) config('vanishop.storefront.page_cache.enabled', false);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::PUBLIC_PAGE, true);
        if (! self::enabled()) {
            return $this->withSession($request, $next);
        }

        $request->attributes->set(self::ATTRIBUTE, true);
        View::share('errors', new ViewErrorBag);

        $response = $next($request);

        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
        }

        $config = (array) config('vanishop.storefront.page_cache');
        if ($request->query->has('signature') || $response->getStatusCode() !== 200 || ! $request->isMethodCacheable()) {
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }

        $fresh = (int) ($config['s_maxage'] ?? 300);
        $stale = (int) ($config['stale_while_revalidate'] ?? 600);
        $expiresAt = PublicPageExpiry::timestamp($request);
        if ($expiresAt !== null) {
            $remaining = max(0, $expiresAt - time());
            $fresh = min($fresh, $remaining);
            $stale = min($stale, $remaining - $fresh);
        }
        $response->headers->set('Cache-Control', sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', $fresh, $stale));
        // Ngôn ngữ nằm trong URL (ADR-038) nên không Vary theo header ngôn ngữ.
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }

    /**
     * Không CDN: chạy phần nhóm `web` đã bỏ (trang công khai chỉ có GET nên không cần kiểm CSRF) rồi nhận diện khách.
     */
    private function withSession(Request $request, Closure $next): Response
    {
        $customerSession = $this->router->getMiddleware()['vani.customer-session'];

        $response = (new Pipeline(app()))
            ->send($request)
            ->through([StartSession::class, ShareErrorsFromSession::class, $customerSession])
            ->then($next);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
