<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Modules\Shared\Support\AdminPath;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chạy TRƯỚC StartSession (đầu nhóm "web"). Request vào Admin dùng cookie phiên riêng, giới hạn path
 * theo đường dẫn Admin, hết hạn khi không hoạt động (ADR-020). Cookie XSRF-TOKEN của Admin cũng theo
 * path này nên không đè token của storefront.
 *
 * Mọi request web đều tự đặt cấu hình phiên của mình (Admin hoặc mặc định), nên tiến trình sống lâu
 * (queue worker, Octane, test) không mang cấu hình Admin sang request storefront.
 */
final class ConfigureAdminSession
{
    public const DEFAULTS_BINDING = 'vanishop.session.defaults';

    public function __construct(
        private readonly SessionManager $sessions,
        private readonly Application $app,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<string, mixed> $desired */
        $desired = AdminPath::matches($request)
            ? [
                'session.cookie' => config('vanishop.admin.session_cookie'),
                'session.path' => '/'.AdminPath::prefix(),
                'session.lifetime' => config('vanishop.admin.idle_minutes'),
                'session.expire_on_close' => false,
            ]
            : $this->app->make(self::DEFAULTS_BINDING);

        $changed = array_filter($desired, fn (mixed $value, string $key): bool => config($key) !== $value, ARRAY_FILTER_USE_BOTH);

        if ($changed !== []) {
            config($desired);
            // SessionManager giữ store (kèm tên cookie) đã tạo — buộc tạo lại theo cấu hình mới.
            $this->sessions->forgetDrivers();
            $this->app->forgetInstance('session.store');
        }

        return $next($request);
    }

    /**
     * Chụp cấu hình phiên mặc định (storefront) lúc boot.
     *
     * @return array<string, mixed>
     */
    public static function snapshotDefaults(): array
    {
        return [
            'session.cookie' => config('session.cookie'),
            'session.path' => config('session.path'),
            'session.lifetime' => config('session.lifetime'),
            'session.expire_on_close' => config('session.expire_on_close'),
        ];
    }
}
