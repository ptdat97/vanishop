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

    /**
     * Đánh dấu extension point bắt buộc (ADR-029): Extension từ chối tắt plugin cung cấp implementation đang bật
     * cuối cùng, `vani:plugin:doctor` báo lỗi khi thiếu.
     */
    public function requires(string $tag, Requirement $requirement, string $label): void;

    /**
     * @return array<string, array{requirement: Requirement, label: string}> tag => yêu cầu
     */
    public function requirements(): array;

    /**
     * Nguồn đóng góp implementation cho tag: plugin id, hoặc null cho implementation của Core.
     * Gồm cả plugin đang tắt (provider đã nạp) — để kiểm tra trước khi tắt.
     *
     * @return list<string|null>
     */
    public function providers(string $tag): array;

    /**
     * Chặn tắt plugin khi implementation của nó còn việc dở dang (0.3.16): `$check` nhận implementation do plugin đóng
     * góp cho `$tag`, trả lý do (vd. "còn 3 khoản thanh toán đang chờ qua cổng vnpay") hoặc null. Module sở hữu dữ liệu
     * đăng ký (Payment: khoản chờ/giữ tiền; Fulfillment: vận đơn chưa kết thúc) — tắt cổng/hãng giữa chừng làm IPN,
     * webhook trả 404 và tiền/hàng không được ghi nhận.
     *
     * @param  callable(object): (string|null)  $check
     */
    public function guardDisable(string $tag, callable $check): void;

    /**
     * Implementation có được chọn cho GIAO DỊCH MỚI không (0.3.23): false khi plugin sở hữu đang `draining`. Module dùng ở
     * điểm khởi tạo giao dịch (phương thức thanh toán ở checkout, phương thức giao, hãng cho vận đơn mới); các luồng xử lý
     * giao dịch đang dở (callback, webhook, tra cứu, hoàn tiền) không lọc theo đây.
     */
    public function acceptsNewTransactions(object $implementation): bool;

    /**
     * Lý do không nên tắt `$pluginId` lúc này (rỗng = tắt được). `vani:plugin:disable --force` bỏ qua (có audit).
     *
     * @return list<string>
     */
    public function disableBlockers(string $pluginId): array;

    /**
     * Loại plugin `kind` (manifest) ứng với extension point `tag` — module sở hữu contract khai báo; doctor cảnh báo
     * `kind_mismatch` khi plugin khai loại này mà không đóng góp implementation nào cho tag.
     */
    public function kindContract(string $kind, string $tag): void;

    /**
     * @return array<string, string> kind => tag
     */
    public function kindContracts(): array;

    /**
     * Kiểm tra cấu hình vận hành do module sở hữu đăng ký cho `vani:plugin:doctor` (0.3.40) — Extension (vòng 0) không
     * biết nghiệp vụ, chỉ chạy kiểm tra. `$check` trả thông điệp cảnh báo hoặc null (ổn); ném lỗi → doctor báo
     * `doctor_check_failed`. Mức cảnh báo: thiếu hẳn implementation bắt buộc dùng `requires()` (lỗi).
     *
     * @param  callable(): (string|null)  $check
     */
    public function doctorCheck(string $code, callable $check): void;

    /**
     * @return array<string, callable(): (string|null)> mã => kiểm tra
     */
    public function doctorChecks(): array;
}
