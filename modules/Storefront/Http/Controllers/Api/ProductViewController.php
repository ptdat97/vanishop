<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Customer\Contracts\CustomerSessions;

/**
 * Storefront API `POST /products/views` (ADR-044, thay `/_vani/xem`): beacon lượt xem PDP (§3.5-2), JS gửi sau khi trang
 * tải (trang cache CDN được, không ghi DB lúc render). Lọc trùng theo mã ngẫu nhiên của trình duyệt cùng nguồn, phiên hoặc IP + trình duyệt. Miễn CSRF —
 * chỉ cộng lượt xem, đã lọc trùng theo phiên. Luôn 204.
 */
final class ProductViewController
{
    public function __invoke(Request $request, ProductViewStatistics $views): Response
    {
        $productId = (int) $request->input('product_id');
        if ($productId > 0) {
            $viewerId = $request->input('viewer_id');
            $viewer = match (true) {
                $request->hasSession() && is_string($viewerId) && preg_match('/\A[a-f0-9]{32}\z/', $viewerId) === 1 => 'browser:'.$viewerId,
                $request->hasSession() && $request->cookies->has((string) config('session.cookie')) => 'session:'.$request->session()->getId(),
                default => (string) $request->ip().'|'.$request->userAgent(),
            };
            $customer = $request->attributes->get(CustomerSessions::REQUEST_ATTRIBUTE);
            $views->record($productId, $viewer, $customer === null ? null : (int) $customer->id);
        }

        return response()->noContent()->header('Cache-Control', 'private, no-store');
    }
}
