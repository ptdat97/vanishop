<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks\Points;

use Inertia\Response;
use Inertia\ResponseFactory;
use Modules\Extension\Application\Hooks\HookManager;

/**
 * Điểm mở rộng tự động cho MỌI trang Admin (Inertia): filter `vani.admin.page.<component>` trên props trước khi render.
 * Tên: component viết thường, `::` và `/` thành `.` — `Ordering::Orders/Show` → `vani.admin.page.ordering.orders.show`.
 * Chỉ chạy khi có listener; lỗi listener bị bỏ qua (khai báo `on_error: skip`).
 */
final class HookedInertiaFactory extends ResponseFactory
{
    public function render($component, $props = []): Response
    {
        if (is_string($component) && is_array($props)) {
            $hook = self::hookName($component);
            $hooks = app(HookManager::class);
            if ($hooks->hasListeners($hook)) {
                $props = $hooks->filter($hook, $props, $component);
            }
        }

        return parent::render($component, $props);
    }

    public static function hookName(string $component): string
    {
        return 'vani.admin.page.'.strtolower(str_replace(['::', '/'], '.', $component));
    }
}
