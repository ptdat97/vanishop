<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Shared\Contracts\PluginApi;
use Modules\Storefront\Contracts\Data\BlockContext;
use Modules\Storefront\Contracts\Data\BuilderScope;

/**
 * Surface có nội dung riêng theo từng đối tượng (vd. từng thương hiệu, danh mục, bộ sưu tập). Builder Engine lưu block
 * theo surface + phạm vi, dùng chung trình biên tập, xem trước, ghi nhận ảnh và render của Builder; surface chỉ cho biết
 * phạm vi của trang đang hiển thị và mô tả phạm vi cho Admin.
 *
 * Tại một slot, vùng có block riêng của phạm vi thay block chung (surface không phạm vi) của cùng slot; vùng chưa đặt
 * block riêng vẫn hiện block chung. Surface phạm vi không có tab ở Admin → Nội dung trang: plugin mở trình biên tập tại
 * `route('admin.storefront.builder.scope.edit', [surface, scope])`, thường từ nút ở trang sửa đối tượng (`adminLink`).
 */
#[PluginApi]
interface ScopedBuilderSurface extends BuilderSurface
{
    /** Mã phạm vi hợp lệ: chữ thường, số, `-`, `_`, tối đa 64 ký tự (vd. `brand-12`). */
    public const SCOPE_PATTERN = '[a-z0-9][a-z0-9_-]{0,63}';

    /** Phạm vi của trang đang render theo ngữ cảnh công khai; null = trang không có nội dung riêng của surface này. */
    public function scopeOf(BlockContext $context): ?string;

    /** Mô tả phạm vi cho Admin; null = phạm vi không tồn tại (trình biên tập trả 404). */
    public function describeScope(string $scope): ?BuilderScope;
}
