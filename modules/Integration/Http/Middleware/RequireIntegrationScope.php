<?php

declare(strict_types=1);

namespace Modules\Integration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Integration\Contracts\IntegrationAccessDenied;
use Modules\Integration\Persistence\Models\IntegrationClient;
use Symfony\Component\HttpFoundation\Response;

final class RequireIntegrationScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $client = $request->attributes->get(AuthenticateIntegrationClient::ATTRIBUTE);
        if (! $client instanceof IntegrationClient || ! $client->hasScope($scope)) {
            throw IntegrationAccessDenied::insufficientScope($scope);
        }

        return $next($request);
    }
}
