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
use Modules\Storefront\Application\PageCache\CachedResponses;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trang công khai (trang chủ, danh mục, thương hiệu, tìm kiếm, sản phẩm, trang plugin `cacheable`) — storefront §5, ADR-051.
 * Chế độ `vanishop.storefront.page_cache.mode` (`VANI_PAGE_CACHE`):
 *
 * - **`app` (mặc định)**: không phiên, HTML giống nhau với mọi khách, không `Set-Cookie`; phần riêng của khách do JS lấy từ
 *   `GET /_vani/phien`. HTML lưu trong ứng dụng kèm tag dữ liệu đã dùng, hết hiệu lực khi tag bị xoá (lô mỗi phút) hoặc
 *   tới hạn tối đa. Header `X-Vani-Cache: HIT | MISS | BYPASS` (+ `X-Vani-Cache-Reason`).
 * - **`cdn`**: như `app`, thêm `Cache-Control: public, s-maxage` và header danh sách tag cho CDN.
 * - **`off`**: chạy lại phiên + nhận diện khách như mọi trang khác → HTML render sẵn phần riêng của khách, không cache.
 *
 * Không lưu: request khác GET/HEAD, phản hồi khác 200, link xem trước có chữ ký, tìm kiếm có từ khoá, quá nhiều giá trị
 * lọc, trang có khối gọi `PageCacheTags::uncacheable()`, nhân viên đang xem trước khi bảo trì.
 */
final class PublicPageCache
{
    /** Có mặt khi trang chạy không phiên (`app`, `cdn`): HTML chung cho mọi khách. */
    public const ATTRIBUTE = 'vani.page_cache';

    /** Có mặt trên mọi trang công khai, ở mọi chế độ (SEO: canonical, hreflang). */
    public const PUBLIC_PAGE = 'vani.public_page';

    /** Middleware bỏ khỏi route trang công khai. */
    public const SESSIONLESS = [...SessionlessRoutes::MIDDLEWARE, 'vani.customer-session'];

    public const MODE_APP = 'app';

    public const MODE_CDN = 'cdn';

    public const MODE_OFF = 'off';

    public const HEADER = 'X-Vani-Cache';

    /** Tag của trang trả về (gỡ lỗi: `vani:page-cache:tags`). */
    public const TAGS_ATTRIBUTE = 'vani.page_cache.tags';

    public function __construct(
        private readonly Router $router,
        private readonly CachedResponses $responses,
    ) {}

    public static function mode(): string
    {
        $mode = (string) config('vanishop.storefront.page_cache.mode', self::MODE_APP);

        return in_array($mode, [self::MODE_APP, self::MODE_CDN, self::MODE_OFF], true) ? $mode : self::MODE_APP;
    }

    /** Trang công khai chạy không phiên (HTML chung cho mọi khách). */
    public static function sessionless(): bool
    {
        return self::mode() !== self::MODE_OFF;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::PUBLIC_PAGE, true);
        if (! self::sessionless()) {
            return $this->withSession($request, $next);
        }

        $request->attributes->set(self::ATTRIBUTE, true);
        View::share('errors', new ViewErrorBag);

        return $this->responses->respond($request, $next);
    }

    /**
     * `off`: chạy phần nhóm `web` đã bỏ (trang công khai chỉ có GET nên không cần kiểm CSRF) rồi nhận diện khách.
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
