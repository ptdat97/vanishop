<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Shared\Contracts\PluginApi;
use Modules\Storefront\Contracts\Data\BuilderZone;

/**
 * Extension point (tag `vani.storefront.builder_surfaces`, storefront §4): một màn hình của Builder Engine tại
 * Admin → Giao diện → Nội dung trang. Surface gom các vùng nhận block, thường là slot đã có của theme; Core lưu cấu hình,
 * validate theo schema block và render. Plugin thêm nội dung theo ba cách, kết hợp được:
 * block mới (`StorefrontBlock`), surface mới trên slot có sẵn, slot mới của plugin (`builder: true` trong hooks.php).
 *
 * Block theo ngữ cảnh dùng ContextualStorefrontBlock để nhận dữ liệu công khai theo URL.
 */
#[PluginApi]
interface BuilderSurface
{
    public const TAG = 'vani.storefront.builder_surfaces';

    /** Mã ổn định (kebab/snake case, ≤ 40 ký tự), dùng trong URL Admin và khoá cấu hình. */
    public function key(): string;

    public function label(): string;

    /**
     * @return list<BuilderZone>
     */
    public function zones(): array;
}
