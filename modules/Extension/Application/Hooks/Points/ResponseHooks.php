<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks\Points;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Extension\Application\Hooks\HookManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware `vani.response-hooks:<tiền tố>`: điểm mở rộng tự động cho MỌI route JSON của một bề mặt API.
 * Filter `<tiền tố>.<tên route bỏ phần nhóm>` trên body JSON đã trình bày — vd. route `api.storefront.v1.products.show`
 * → `vani.api.storefront.products.show`. Chỉ phản hồi thành công, chỉ khi có listener; lỗi listener bị bỏ qua.
 */
final class ResponseHooks
{
    public function __construct(private readonly HookManager $hooks) {}

    public function handle(Request $request, Closure $next, string $prefix, string $strip = ''): Response
    {
        $response = $next($request);
        $route = (string) $request->route()?->getName();
        if (! $response instanceof JsonResponse || $route === '' || ! $response->isSuccessful()) {
            return $response;
        }

        $hook = $prefix.'.'.($strip !== '' && str_starts_with($route, $strip) ? substr($route, strlen($strip)) : $route);
        if (! $this->hooks->hasListeners($hook)) {
            return $response;
        }

        $data = $response->getData(true);
        if (is_array($data)) {
            $response->setData($this->hooks->filter($hook, $data, $request));
        }

        return $response;
    }
}
