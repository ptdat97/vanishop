<?php

declare(strict_types=1);

use Modules\Extension\Application\Hooks\CallerPlugin;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Contracts\Data\LinkTarget;
use Modules\Storefront\Contracts\LinkResolver;

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
     * `vani_add_filter('vani.admin.page.ordering.orders.show', fn (array $props) => [...], 110)`. Priority nhỏ chạy trước,
     * mặc định HookManager::DEFAULT_PRIORITY (100).
     */
    function vani_add_filter(string $hook, callable $callback, int $priority = HookManager::DEFAULT_PRIORITY): void
    {
        app(HookManager::class)->onFilter($hook, $callback, $priority, app(CallerPlugin::class)->resolve());
    }
}

if (! function_exists('vani_add_action')) {
    function vani_add_action(string $hook, callable $callback, int $priority = HookManager::DEFAULT_PRIORITY): void
    {
        app(HookManager::class)->onAction($hook, $callback, $priority, app(CallerPlugin::class)->resolve());
    }
}

if (! function_exists('vani_add_slot')) {
    /**
     * Chèn nội dung vào slot giao diện (0.3.54): storefront trả `HtmlString`/`SlotView`, Admin trả mảng mô tả panel/card.
     * `vani_add_slot('vani.storefront.pdp.after_price', fn (array $product) => new HtmlString('…'))`. Listener lỗi chỉ bị
     * bỏ qua, không làm hỏng trang.
     */
    function vani_add_slot(string $hook, callable $callback, int $priority = HookManager::DEFAULT_PRIORITY): void
    {
        app(HookManager::class)->onSlot($hook, $callback, $priority, app(CallerPlugin::class)->resolve());
    }
}

/*
| Bí danh quen thuộc cho người viết plugin (ADR-036): cùng HookManager, cùng bảo đảm như vani_*. Core đặt điểm
| `hook_filter('vani.service.<module>.<kết quả>', $value, ...)` ngay tại kết quả của service nghiệp vụ;
| plugin nghe bằng `add_hook_filter(...)` trong boot(). Danh mục: docs/04-extension/extension-point-catalog.md §3.10
*/

if (! function_exists('hook_filter')) {
    function hook_filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(HookManager::class)->filter($hook, $value, ...$args);
    }
}

if (! function_exists('hook_action')) {
    function hook_action(string $hook, mixed ...$args): void
    {
        app(HookManager::class)->action($hook, ...$args);
    }
}

if (! function_exists('add_hook_filter')) {
    function add_hook_filter(string $hook, callable $callback, int $priority = HookManager::DEFAULT_PRIORITY): void
    {
        app(HookManager::class)->onFilter($hook, $callback, $priority, app(CallerPlugin::class)->resolve());
    }
}

if (! function_exists('add_hook_action')) {
    function add_hook_action(string $hook, callable $callback, int $priority = HookManager::DEFAULT_PRIORITY): void
    {
        app(HookManager::class)->onAction($hook, $callback, $priority, app(CallerPlugin::class)->resolve());
    }
}

if (! function_exists('add_hook_slot')) {
    function add_hook_slot(string $hook, callable $callback, int $priority = HookManager::DEFAULT_PRIORITY): void
    {
        app(HookManager::class)->onSlot($hook, $callback, $priority, app(CallerPlugin::class)->resolve());
    }
}

/*
| URL storefront theo ngôn ngữ (ADR-037/038) — cho view theme, view và code plugin. Class trong modules/ dùng
| Modules\Shared\Support\LocalizedUrls trực tiếp.
*/

if (! function_exists('vani_route')) {
    /**
     * URL storefront theo ngôn ngữ hiện hành (hoặc `$locale`): `vani_route('storefront.product', $slug)` → `/san-pham/…`
     * trên trang tiếng Việt, `/en/products/…` trên trang tiếng Anh. Route chưa có bản theo ngôn ngữ → URL mặc định.
     */
    function vani_route(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return LocalizedUrls::route($name, $parameters, $locale, $absolute);
    }
}

if (! function_exists('vani_link')) {
    /**
     * URL của link có kiểu theo ngôn ngữ hiện hành; null khi đối tượng đã ẩn/xoá. `vani_link(['type' => 'category', 'value' => '12'])`.
     *
     * @param  LinkTarget|array<string, mixed>  $target
     */
    function vani_link(LinkTarget|array $target): ?string
    {
        $target = is_array($target) ? LinkTarget::fromArray($target) : $target;
        if ($target === null) {
            return null;
        }
        $resolved = app(LinkResolver::class)->resolve($target, app()->getLocale());

        return $resolved->exists ? $resolved->url : null;
    }
}
