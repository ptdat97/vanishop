<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

/**
 * Service contract: đăng ký và lấy implementation của một extension point (tag) CÓ HIỆU LỰC trong phạm vi
 * hiện tại.
 *
 * - Module Core đăng ký implementation mặc định bằng `tag()` — luôn có hiệu lực.
 * - Plugin đóng góp implementation qua `PluginServiceProvider::contribute()` — chỉ có hiệu lực khi plugin
 *   được bật cho owner/brand/channel của CurrentContext.
 *
 * Registry của module (PaymentGateway, PromotionRule, ShippingCarrier…) dùng contract này thay cho
 * `Container::tagged()`, để plugin bật theo brand này không lọt sang brand khác.
 *
 * @see docs/04-extension/extension-model.md
 */
interface Extensions
{
    /**
     * Đăng ký implementation mặc định của Core cho một extension point.
     *
     * @param  class-string|string|list<class-string|string>  $abstracts
     */
    public function tag(string|array $abstracts, string $tag): void;

    /**
     * Ghi nhận implementation do plugin đóng góp (gọi từ `PluginServiceProvider::contribute()`).
     * Plugin phải `bind()` abstract trong `register()` nếu constructor cần tham số cấu hình.
     *
     * @param  class-string|string  $abstract
     */
    public function contribute(string $tag, string $abstract, string $pluginId): void;

    /**
     * @return list<object>
     */
    public function tagged(string $tag): array;

    /**
     * Plugin sở hữu implementation (null = Core hoặc không do plugin nào đóng góp).
     */
    public function ownerOf(object $implementation): ?string;
}
