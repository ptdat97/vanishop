<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

/**
 * Xoá cache trang theo tag (ADR-051). Mặc định **gom theo lô**: tag vào hàng đợi, lệnh `vani:page-cache:invalidate` (mỗi
 * phút) xoá một lần cho cả lô — thao tác hàng loạt (nhập file, sửa giá nhiều sản phẩm) không xoá cache liên tục. Gọi trong
 * transaction thì chỉ ghi hàng đợi sau khi commit (rollback thì bỏ).
 *
 * Sau mỗi lần xoá, Core phát `PageCacheTagsInvalidated` để plugin xoá CDN theo tag.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface PageCacheInvalidator
{
    /** Xoá trong lô kế tiếp (trễ tối đa ~1 phút). */
    public function invalidate(string ...$tags): void;

    /** Xoá ngay, không chờ lô (thay đổi khách phải thấy tức thì). */
    public function invalidateNow(string ...$tags): void;

    /** Xoá ngay toàn bộ cache trang. */
    public function flush(): void;
}
