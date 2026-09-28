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
 * Storefront: domain (+ path prefix) → channel → brand, locale → CurrentContext. Không khớp → 404.
 */
final class ResolveChannel
{
    public function __construct(
        private readonly ChannelResolver $channels,
        private readonly CurrentContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $channel = $this->channels->resolve($request->getHost(), $request->getPathInfo());

        abort_if($channel === null, 404);

        $this->context->set(new ContextScope(
            actor: Actor::guest(),
            channelId: $channel->id,
            brandIds: $channel->brandIds,
            locale: $channel->locale,
        ));

        App::setLocale($channel->locale);
        $request->attributes->set('channel', $channel);

        return $next($request);
    }
}
