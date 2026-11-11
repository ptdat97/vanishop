<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\StoreLocale;
use Symfony\Component\HttpFoundation\Response;

/**
 * Khách vãng lai + ngôn ngữ. Storefront API: header X-Vani-Locale (mặc định ngôn ngữ cửa hàng, không theo
 * Accept-Language). Trang web native: ngôn ngữ cố định theo nhóm route (`vani.storefront-context:en`, ADR-038) — lấy
 * từ URL, bỏ qua header. Nguồn yêu cầu lấy từ header X-Vani-Source trong danh sách
 * `vanishop.storefront.sources` của cửa hàng (vòng 0 không biết tên kênh bán), ngoài danh sách → mục đầu tiên.
 */
final class ResolveStorefrontContext
{
    public const LOCALE_HEADER = 'X-Vani-Locale';

    public const SOURCE_HEADER = 'X-Vani-Source';

    public function __construct(private readonly CurrentContext $context) {}

    public function handle(Request $request, Closure $next, ?string $routeLocale = null): Response
    {
        $requested = $routeLocale ?? (string) $request->headers->get(self::LOCALE_HEADER, '');
        $locale = StoreLocale::isSupported($requested) ? $requested : StoreLocale::default();

        $source = (string) $request->headers->get(self::SOURCE_HEADER, '');
        $sources = (array) config('vanishop.storefront.sources');
        $request->attributes->set('order_source', in_array($source, $sources, true) ? $source : (string) ($sources[0] ?? ''));

        $this->context->set(new ContextScope(Actor::guest(), $locale));
        App::setLocale($locale);

        return $next($request);
    }
}
