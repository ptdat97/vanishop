<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cổng vào Admin: IP allowlist (trả 404 để không lộ sự tồn tại của Admin) và header noindex.
 */
final class AdminGate
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $allowlist */
        $allowlist = config('vanishop.admin.ip_allowlist', []);

        if ($allowlist !== [] && ! IpUtils::checkIp((string) $request->ip(), $allowlist)) {
            Log::warning('Truy cập Admin bị chặn bởi IP allowlist.', ['ip' => $request->ip(), 'path' => $request->path()]);
            abort(404);
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
