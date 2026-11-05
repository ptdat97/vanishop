<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Catalog\Contracts\ProductViewStatistics;

/**
 * Beacon lượt xem PDP (§3.5-2): JS gửi sau khi trang tải (trang cache CDN được, không ghi DB lúc render). Miễn CSRF —
 * chỉ cộng lượt xem, đã lọc trùng theo phiên. Luôn 204.
 */
final class ProductViewController
{
    public function __invoke(Request $request, ProductViewStatistics $views): Response
    {
        $styleId = (int) $request->input('style_id');
        if ($styleId > 0) {
            // Phiên chỉ ổn định khi request đã mang cookie phiên; lần đầu (trang cache CDN, chưa có cookie) dùng IP + trình duyệt.
            $viewer = $request->hasSession() && $request->cookies->has((string) config('session.cookie'))
                ? $request->session()->getId()
                : (string) $request->ip().'|'.$request->userAgent();
            $customer = $request->attributes->get('customer');
            $views->record($styleId, $viewer, $customer === null ? null : (int) $customer->id);
        }

        return response()->noContent()->header('Cache-Control', 'private, no-store');
    }
}
