<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Modules\Catalog\Domain\OptionDisplayType;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;

/**
 * Mã lọc của một giá trị tuỳ chọn ở trang danh sách (ADR-045): loại kiểu ô màu lọc theo nhóm màu (`white`, `black`…) —
 * khách tìm "áo trắng" chứ không tìm đúng sắc "Ivory"; loại khác lọc theo mã giá trị (`S`, `COTTON`…). Giá trị ô màu chưa
 * có nhóm màu thì không lọc được. Dùng chung cho tìm kiếm trên DB, tài liệu chỉ mục ngoài và nhãn bộ lọc.
 */
final class OptionFilterToken
{
    /** Biểu thức SQL tính mã lọc từ `ot` (option_types) và `ov` (option_values). */
    public const SQL = "case when ot.display_type = 'swatch' then ov.family else ov.code end";

    public static function of(OptionType $type, OptionValue $value): ?string
    {
        return $type->display_type === OptionDisplayType::Swatch ? $value->family : $value->code;
    }

    /** Phần tử của `ProductDocument::$options`: `<mã loại>:<mã lọc>`. */
    public static function document(string $typeCode, string $token): string
    {
        return "{$typeCode}:{$token}";
    }
}
