<?php

declare(strict_types=1);

namespace Modules\Brand\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Brand workspace trong Admin: route có tham số {brand} (slug). Nhân viên chỉ vào được brand trong phạm vi
 * của mình (ngoài phạm vi → 404, không lộ sự tồn tại); CurrentContext được thu hẹp về đúng brand đó.
 * Chạy sau vani.staff-context.
 */
final class ResolveAdminBrand
{
    public function __construct(private readonly CurrentContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('brand');
        $brand = is_string($slug) ? Brand::query()->where('slug', $slug)->first() : null;

        abort_if($brand === null || ! $this->context->scope()->allowsBrand($brand->id), 404);

        $scope = $this->context->scope();
        $this->context->set(new ContextScope($scope->actor, $scope->channelId, [$brand->id], $scope->locale));

        $request->attributes->set('workspace_brand', $brand);
        $request->route()?->setParameter('brand', $brand);
        URL::defaults(['brand' => $brand->slug]);

        return $next($request);
    }
}
