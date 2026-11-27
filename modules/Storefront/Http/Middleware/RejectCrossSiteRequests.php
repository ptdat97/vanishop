<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thêm vào giỏ được miễn kiểm token CSRF (trang cache không có token trong HTML). Lớp chặn chính là cookie phiên
 * `SameSite=Lax`; middleware này chặn thêm request từ site khác
 * nếu ai đó đổi `SESSION_SAME_SITE=none`, hoặc trang tấn công nằm trên subdomain khác cùng tên miền (trình duyệt coi là
 * cùng site nên vẫn gửi cookie Lax):
 *
 * - Có `Sec-Fetch-Site` (trình duyệt hiện đại) mà khác `same-origin` → chặn.
 * - Không có nhưng có `Origin` khác host → chặn.
 * - Không có cả hai (client không phải trình duyệt, trình duyệt rất cũ) → cho qua: CSRF chỉ khai thác được qua trình duyệt.
 */
final class RejectCrossSiteRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! self::crossSite($request)) {
            return $next($request);
        }

        $message = __('storefront::messages.cross_site_request');
        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse(['error' => array_filter([
                'code' => 'storefront.cross_site_request',
                'message' => $message,
                'correlation_id' => Context::get('correlation_id'),
            ])], 403);
        }

        abort(403, $message);
    }

    public static function crossSite(Request $request): bool
    {
        $site = (string) $request->headers->get('Sec-Fetch-Site', '');
        if ($site !== '') {
            return $site !== 'same-origin';
        }
        $origin = (string) $request->headers->get('Origin', '');

        return $origin !== '' && strtolower((string) parse_url($origin, PHP_URL_HOST)) !== strtolower($request->getHost());
    }
}
