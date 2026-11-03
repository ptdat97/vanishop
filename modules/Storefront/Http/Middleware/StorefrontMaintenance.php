<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Modules\Storefront\Application\Maintenance;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bảo trì storefront (reference-parity §3.2): trang storefront, trang plugin và Storefront API trả 503 + `Retry-After`.
 * Chỉ gắn vào route storefront — IPN/webhook (`/api/integrations/*`), Integration API, `/health`, Admin không qua đây.
 * Nhân viên có cookie xem trước hợp lệ xem bình thường, phản hồi không cache công khai (không lọt vào CDN).
 */
final class StorefrontMaintenance
{
    public const RETRY_AFTER = 600;

    public function __construct(private readonly Maintenance $maintenance) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenance->enabled()) {
            return $next($request);
        }

        if ($this->maintenance->bypassValid($request->cookies->get(Maintenance::COOKIE))) {
            $response = $next($request);
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }

        $message = $this->maintenance->message() !== '' ? $this->maintenance->message() : __('storefront::messages.maintenance');
        $headers = ['Retry-After' => (string) self::RETRY_AFTER, 'Cache-Control' => 'private, no-store'];

        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse(['error' => array_filter([
                'code' => 'storefront.maintenance',
                'message' => $message,
                'correlation_id' => Context::get('correlation_id'),
            ])], 503, $headers);
        }

        return response()->view('theme::pages.maintenance', ['message' => $message], 503, $headers);
    }
}
