<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

/**
 * Service contract: đăng ký và lấy implementation CÓ HIỆU LỰC của một extension point (tag).
 *
 * - Module Core đăng ký implementation mặc định bằng `tag()` — luôn có hiệu lực.
 * - Plugin đóng góp implementation qua `PluginServiceProvider::contribute()` — chỉ có hiệu lực khi plugin
 *   đang bật.
 *
 * Registry của module (PaymentGateway, PromotionRule, ShippingCarrier…) dùng contract này thay cho
 * `Container::tagged()`, để plugin đã tắt không còn tham gia.
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
     * Implementation có hiệu lực của `$tag` là instance của `$interface`, đánh chỉ mục theo `$key($implementation)`
     * (mặc định `code()`); trùng khoá thì implementation đăng ký trước thắng (Core trước plugin).
     *
     * @template T of object
     *
     * @param  class-string<T>  $interface
     * @param  (callable(T): string)|null  $key
     * @return array<string, T>
     */
    public function implementations(string $tag, string $interface, ?callable $key = null): array;

    /**
     * Gọi một implementation trên luồng **tuỳ chọn** (kiểm tra khả dụng, báo cước, tìm kiếm…): lỗi được ghi log kèm
     * plugin sở hữu và trả `$fallback`. Plugin lỗi liên tục (5 lần/phút) bị bỏ qua 5 phút (circuit breaker) — khi
     * đó trả `$fallback` ngay. KHÔNG dùng cho luồng mà lỗi phải làm hỏng giao dịch (validate, totals khi đặt hàng).
     *
     * @template R
     *
     * @param  callable(): R  $call
     * @param  R  $fallback
     * @return R
     */
    public function call(object $implementation, callable $call, mixed $fallback, string $operation): mixed;

    /**
     * Implementation có `code() === $code` trong các implementation có hiệu lực; không có → thử `$fallbackCode`
     * (ghi cảnh báo — cấu hình trỏ tới implementation của plugin đã tắt/không tồn tại không làm hỏng flow).
     */
    public function select(string $tag, string $code, ?string $fallbackCode = null): ?object;

    /**
     * Plugin sở hữu implementation (null = Core hoặc không do plugin nào đóng góp).
     */
    public function ownerOf(object $implementation): ?string;
}
