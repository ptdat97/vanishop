<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storefront API: khách vãng lai + ngôn ngữ (mặc định `vi`, đổi bằng header X-Vani-Locale — không theo
 * Accept-Language). Nguồn đơn (`web`/`app`/`zalo`) lấy từ header X-Vani-Source, mặc định `web`.
 */
final class ResolveStorefrontContext
{
    public const LOCALE_HEADER = 'X-Vani-Locale';

    public const SOURCE_HEADER = 'X-Vani-Source';

    public const SOURCES = ['web', 'app', 'zalo'];

    public function __construct(private readonly CurrentContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requested = (string) $request->headers->get(self::LOCALE_HEADER, '');
        $locale = in_array($requested, (array) config('vanishop.locale.supported', ['vi']), true)
            ? $requested
            : (string) config('vanishop.locale.default', 'vi');

        $source = (string) $request->headers->get(self::SOURCE_HEADER, '');
        $request->attributes->set('order_source', in_array($source, self::SOURCES, true) ? $source : 'web');

        $this->context->set(new ContextScope(Actor::guest(), $locale));
        App::setLocale($locale);

        return $next($request);
    }
}
