<?php

declare(strict_types=1);

use Modules\Extension\Application\Hooks\CallerPlugin;
use Modules\Extension\Application\Hooks\HookManager;

/*
| Cú pháp ngắn cho hook — gọi được ở mọi nơi (Core, plugin, view). Vẫn đi qua HookManager nên giữ nguyên các bảo
| đảm của VaniShop: hook phải khai báo (đúng tên hoặc theo mẫu `*.`), kiểm kiểu trả về của filter, listener gắn
| plugin sở hữu (suy ra từ vị trí gọi) và chỉ chạy khi plugin bật, quy tắc lỗi theo loại hook, đo thời gian.
| Không cần khai báo số tham số. Docs: docs/04-extension/extension-model.md §4.2
*/

if (! function_exists('vani_filter')) {
    /**
     * Áp các filter đã đăng ký lên $value. `vani_filter('vani.catalog.product.view_data', $data, $product)`.
     */
    function vani_filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(HookManager::class)->filter($hook, $value, ...$args);
    }
}

if (! function_exists('vani_action')) {
    function vani_action(string $hook, mixed ...$args): void
    {
        app(HookManager::class)->action($hook, ...$args);
    }
}

if (! function_exists('vani_add_filter')) {
    /**
     * Đăng ký filter; trong thư mục plugin thì listener thuộc plugin đó (chỉ chạy khi plugin bật).
     * `vani_add_filter('vani.admin.page.ordering.orders.show', fn (array $props) => [...], 20)`.
     */
    function vani_add_filter(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookManager::class)->onFilter($hook, $callback, $priority, app(CallerPlugin::class)->resolve());
    }
}

if (! function_exists('vani_add_action')) {
    function vani_add_action(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookManager::class)->onAction($hook, $callback, $priority, app(CallerPlugin::class)->resolve());
    }
}
