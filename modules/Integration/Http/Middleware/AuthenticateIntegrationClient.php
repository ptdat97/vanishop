<?php

declare(strict_types=1);

namespace Modules\Integration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Modules\Integration\Contracts\IntegrationAccessDenied;
use Modules\Integration\Domain\HmacSignature;
use Modules\Integration\Persistence\Models\ClientKey;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\StoreLocale;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration API: `X-Vani-Key-Id` + `X-Vani-Signature: t=<unix>,v1=<hmac>` ký trên METHOD.path?query.body.
 * Thành công → CurrentContext là actor `integration`.
 */
final class AuthenticateIntegrationClient
{
    public const ATTRIBUTE = 'integration_client';

    public function __construct(private readonly CurrentContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $keyId = (string) $request->header('X-Vani-Key-Id', '');
        $signature = (string) $request->header('X-Vani-Signature', '');
        if ($keyId === '' || $signature === '') {
            throw IntegrationAccessDenied::unauthenticated();
        }

        $key = ClientKey::query()->with('client')->where('key_id', $keyId)->first();
        if ($key === null || ! $key->isUsable() || ! $key->client->isActive()) {
            throw IntegrationAccessDenied::unauthenticated();
        }

        $client = $key->client;
        if ($client->ip_allowlist !== null && $client->ip_allowlist !== [] && ! IpUtils::checkIp((string) $request->ip(), $client->ip_allowlist)) {
            throw IntegrationAccessDenied::ipNotAllowed();
        }

        $payload = HmacSignature::requestPayload($request->getMethod(), $request->getRequestUri(), $request->getContent());
        if (! HmacSignature::verify($key->secret, $payload, $signature, now()->getTimestamp(), (int) config('vanishop.integration.signature_tolerance', HmacSignature::TOLERANCE_SECONDS))) {
            throw IntegrationAccessDenied::unauthenticated();
        }

        if ($key->last_used_at === null || $key->last_used_at->lt(now()->subMinute())) {
            $key->forceFill(['last_used_at' => now()])->save();
        }

        $request->attributes->set(self::ATTRIBUTE, $client);
        Context::add('integration_client', $client->code);
        $this->context->set(new ContextScope(new Actor(ActorType::Integration, $client->id, $client->code), StoreLocale::default()));

        return $next($request);
    }
}
