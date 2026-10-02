<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Extension\Contracts\Data\FieldDefinition;

/**
 * Extension point (tag `vani.storefront.blocks`, storefront §4, ADR-030 W6): khối của page builder (trang chủ).
 * Quản trị chọn khối + cấu hình theo `fields()` (Core validate); khi render Core gọi `resolve()` rồi render `view()`
 * với dữ liệu trả về. Lỗi ở `resolve()` hoặc lúc render → bỏ khối đó, trang vẫn hiển thị.
 * View: core `theme::blocks.<type>`; plugin dùng namespace view riêng (theme override được).
 */
interface StorefrontBlock
{
    public const TAG = 'vani.storefront.blocks';

    /** Mã ổn định (snake_case), lưu trong cấu hình trang. */
    public function type(): string;

    public function label(): string;

    /**
     * @return list<FieldDefinition>
     */
    public function fields(): array;

    /**
     * Lấy dữ liệu hiển thị qua contract/Query (không I/O mạng đồng bộ).
     *
     * @param  array<string, mixed>  $config  giá trị đã validate theo fields()
     * @return array<string, mixed>
     */
    public function resolve(array $config, string $locale): array;

    public function view(): string;
}
