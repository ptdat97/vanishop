<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Application\PageCache\CachedResponses;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET công khai của Storefront API (danh mục, thương hiệu, bộ sưu tập, sản phẩm, vỏ trang) dùng chung cache trang (ADR-051
 * đợt 4, L60): cùng bộ gom tag, hàng đợi xoá, hạn theo lịch. **Chỉ khách vãng lai**: có `Authorization` hoặc khách đã đăng
 * nhập qua phiên cùng nguồn thì bỏ qua cache (khi ẩn giá, khách đã đăng nhập thấy giá). `VANI_PAGE_CACHE=off` thì không cache.
 * Gắn sau `vani.customer:optional` để biết khách.
 */
final class PublicApiCache
{
    public function __construct(private readonly CachedResponses $responses, private readonly CurrentContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (PublicPageCache::mode() === PublicPageCache::MODE_OFF) {
            return $next($request);
        }

        $bypass = match (true) {
            $request->headers->has('Authorization') => 'authorized',
            $this->context->has() && $this->context->actor()->type === ActorType::Customer => 'signed_in',
            default => null,
        };

        return $this->responses->respond($request, $next, $bypass, api: true);
    }
}
