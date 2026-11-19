<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract: danh mục mã quyền do module/plugin đăng ký lúc boot (vai trò chọn trong danh mục này) — C1.
 * Mỗi quyền tự khai thuộc vai trò mẫu nào (`presets`: `order_ops`, `marketing`, `content`, `analyst` — RolePresets) để
 * Admin → Vai trò tạo nhanh vai trò cho từng team; plugin mới khai thì tự vào đúng mẫu.
 */
#[PluginApi]
interface PermissionRegistry
{
    public const WILDCARD = '*';

    /** Vai trò mẫu: vận hành đơn. */
    public const PRESET_ORDER_OPS = 'order_ops';

    /** Vai trò mẫu: marketing. */
    public const PRESET_MARKETING = 'marketing';

    /** Vai trò mẫu: nội dung (sản phẩm, trang, ảnh). */
    public const PRESET_CONTENT = 'content';

    /** Vai trò mẫu: phân tích dữ liệu (chỉ xem, không thấy thông tin liên hệ của khách). */
    public const PRESET_ANALYST = 'analyst';

    /**
     * @param  string  $description  khoá dịch (dịch lúc hiển thị)
     * @param  list<string>  $presets  vai trò mẫu có quyền này
     * @param  array<string, string>  $params  tham số của khoá dịch
     */
    public function register(string $code, string $description, array $presets = [], array $params = []): void;

    public function has(string $code): bool;

    /**
     * @return array<string, string>
     */
    public function all(): array;

    /**
     * Mã quyền thuộc một vai trò mẫu (gồm `admin.access`).
     *
     * @return list<string>
     */
    public function preset(string $preset): array;
}
