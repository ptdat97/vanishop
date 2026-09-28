<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Modules\Brand\Http\Middleware\ResolveAdminBrand;
use Modules\Channel\Http\Middleware\ResolveApiChannel;
use Modules\Channel\Http\Middleware\ResolveChannel;
use Modules\Identity\Http\Middleware\AdminGate;
use Modules\Identity\Http\Middleware\ConfigureAdminSession;
use Modules\Identity\Http\Middleware\ResolveStaffContext;
use Modules\Shared\Http\ApiErrorRenderer;
use Modules\Shared\Http\Middleware\AssignCorrelationId;
use Modules\Shared\Http\WebBusinessRuleRenderer;
use Modules\Shared\Support\AdminPath;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignCorrelationId::class);
        // Phải chạy trước StartSession để Admin dùng cookie phiên riêng (ADR-020).
        $middleware->web(prepend: [ConfigureAdminSession::class]);
        $middleware->alias(['vani.inertia' => HandleInertiaRequests::class]);
        $middleware->group('vani.admin', [AdminGate::class, HandleInertiaRequests::class]);
        // Phạm vi (nhân viên → brand workspace; kênh storefront) phải có TRƯỚC route model binding,
        // để binding của model có phạm vi brand tự lọc (id của brand khác → 404).
        foreach ([ResolveStaffContext::class, ResolveAdminBrand::class, ResolveChannel::class, ResolveApiChannel::class] as $scopeMiddleware) {
            $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: $scopeMiddleware);
        }
        $middleware->redirectGuestsTo(fn (Request $request): string => AdminPath::matches($request) ? route('admin.login') : '/');
        $middleware->redirectUsersTo(fn (Request $request): string => AdminPath::matches($request) ? route('admin.dashboard') : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(new ApiErrorRenderer);
        $exceptions->render(new WebBusinessRuleRenderer);
    })->create();
