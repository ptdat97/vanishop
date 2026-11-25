<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\AdminPath;
use Modules\Shared\Support\StoreLocale;
use Modules\Tenancy\Contracts\Settings;
use Throwable;

/**
 * Đường dẫn Admin (ADR-020) lưu trong DB (`core.security.admin_path`), chỉ super admin đổi được ở Admin → Đường dẫn
 * Admin hoặc bằng `php artisan vani:admin-path`. Chưa cấu hình → `admin`. Giá trị nạp vào `vanishop.admin.path` khi app
 * bắt đầu boot (trước khi route Admin, Horizon, Pulse được dựng); đọc qua cache, DB chưa sẵn sàng (cài đặt, migrate) thì
 * dùng mặc định.
 */
final class AdminPathSetting
{
    public const DEFAULT = 'admin';

    public const SETTING = 'security.admin_path';

    private const CACHE_KEY = 'vani:admin-path';

    /** Chỉ phụ thuộc Settings: được tạo khi app bắt đầu boot, trước khi provider nào boot. */
    public function __construct(private readonly Settings $settings) {}

    public function current(): string
    {
        try {
            $path = Cache::rememberForever(self::CACHE_KEY, fn (): string => (string) ($this->settings->get('core', self::SETTING) ?? self::DEFAULT));
        } catch (Throwable) {
            return self::DEFAULT;
        }
        if ($this->problem($path, ignoreRoutes: true) !== null) {
            Log::warning('Đường dẫn Admin đã lưu không hợp lệ, dùng mặc định.', ['path' => $path]);

            return self::DEFAULT;
        }

        return $path;
    }

    /**
     * Đổi đường dẫn; `admin` = về mặc định (xoá cấu hình). Route đã cache thì dựng lại cache để áp dụng ngay.
     *
     * @throws ValidationException
     */
    public function change(string $path, ?int $actorId = null): void
    {
        $path = strtolower(trim($path, " /\t\n"));
        $previous = $this->current();
        if ($path === $previous) {
            return;
        }
        $problem = $this->problem($path);
        if ($problem !== null) {
            throw ValidationException::withMessages(['path' => $problem]);
        }

        if ($path === self::DEFAULT) {
            $this->settings->forget('core', self::SETTING);
        } else {
            $this->settings->set('core', self::SETTING, $path);
        }
        Cache::forget(self::CACHE_KEY);
        app(AuditLogger::class)->record('identity.admin_path.changed', 'staff', $actorId ?? 0, ['from' => $previous, 'to' => $path]);

        if (app()->routesAreCached()) {
            Artisan::call('route:cache');
        }
    }

    /** Lý do không dùng được đường dẫn; null = hợp lệ. */
    public function problem(string $path, bool $ignoreRoutes = false): ?string
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $path) !== 1 || strlen($path) > 64) {
            return __('identity::texts.admin_path.format');
        }
        $reserved = [...(array) config('vanishop.reserved_paths', []), ...StoreLocale::supported()];
        if (! $ignoreRoutes) {
            $current = AdminPath::prefix();
            foreach (Router::getRoutes()->getRoutes() as $route) {
                $segment = explode('/', trim($route->uri(), '/'))[0];
                if ($segment !== $current && ! str_starts_with((string) $route->getName(), 'admin.') && ! str_starts_with($segment, '{')) {
                    $reserved[] = $segment;
                }
            }
        }

        return in_array($path, $reserved, true) ? __('identity::texts.admin_path.reserved') : null;
    }
}
