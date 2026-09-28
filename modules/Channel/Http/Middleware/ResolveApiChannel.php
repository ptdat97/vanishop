<?php

declare(strict_types=1);

namespace Modules\Channel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Channel\Contracts\ChannelResolver;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storefront API: kênh xác định bằng header X-Vani-Channel (mã kênh); locale theo kênh hoặc header X-Vani-Locale.
 */
final class ResolveApiChannel
{
    public const HEADER = 'X-Vani-Channel';

    public const LOCALE_HEADER = 'X-Vani-Locale';

    private const SUPPORTED_LOCALES = ['vi', 'en'];

    public function __construct(
        private readonly ChannelResolver $channels,
        private readonly CurrentContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $code = (string) $request->headers->get(self::HEADER, '');
        $channel = $code === '' ? null : $this->channels->byCode($code);

        abort_if($channel === null, 404, __('channel::messages.unknown_channel'));

        // Locale mặc định theo kênh (không theo Accept-Language của trình duyệt); client muốn khác thì gửi X-Vani-Locale.
        $requested = (string) $request->headers->get(self::LOCALE_HEADER, '');
        $locale = in_array($requested, self::SUPPORTED_LOCALES, true) ? $requested : $channel->locale;

        $this->context->set(new ContextScope(Actor::guest(), $channel->id, $channel->brandIds, $locale));
        App::setLocale($locale);
        $request->attributes->set('channel', $channel);

        return $next($request);
    }
}
