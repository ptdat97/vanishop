<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

use Attribute;

/**
 * Đánh dấu class/interface/enum thuộc **API cho plugin** (ADR-048, architecture-consolidation P2–P3). Plugin chỉ được dùng
 * class có thuộc tính này (R5); phần còn lại của `Contracts`/`Events` là API giữa module, đổi được mà không tăng
 * `vanishop.extension_api`. Chữ ký các class đánh dấu được chụp vào `tests/Architecture/plugin-api.snapshot`.
 *
 * - `stable`: xoá/đổi chữ ký → tăng **major** của `extension_api`.
 * - `experimental`: điểm mở rộng mới, có thể đổi ở bản **minor**; lên `stable` sau một vòng phát hành có plugin thật dùng.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class PluginApi
{
    public const STABLE = 'stable';

    public const EXPERIMENTAL = 'experimental';

    public function __construct(public string $stability = self::STABLE) {}
}
