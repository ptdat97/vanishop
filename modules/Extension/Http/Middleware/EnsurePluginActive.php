<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Extension\Application\Plugins\PluginActivation;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route của plugin chỉ truy cập được khi plugin đang bật trong phạm vi hiện tại.
 */
final class EnsurePluginActive
{
    public function __construct(private readonly PluginActivation $activation) {}

    public function handle(Request $request, Closure $next, string $pluginId): Response
    {
        abort_unless($this->activation->isActive($pluginId), 404);

        return $next($request);
    }
}
