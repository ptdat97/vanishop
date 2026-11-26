<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;

/**
 * Mã lọc của một giá trị tuỳ chọn ở trang danh sách (ADR-045): loại kiểu ô màu lọc theo nhóm màu (`white`, `black`…) —
 * khách tìm "áo trắng" chứ không tìm đúng sắc "Ivory"; loại khác lọc theo mã giá trị (`S`, `COTTON`…). Giá trị ô màu chưa
 * có nhóm màu thì không lọc được. Lưu sẵn ở `option_values.filter_token` (db-refactor §4.2); dùng cho read model và nhãn bộ lọc.
 */
final class OptionFilterToken
{
    public static function of(OptionType $type, OptionValue $value): ?string
    {
        return OptionValue::filterTokenFor($type->display_type, $value->code, $value->family);
    }

    /** Token lọc tuỳ chọn: `<mã loại>:<mã lọc>`. */
    public static function document(string $typeCode, string $token): string
    {
        return "{$typeCode}:{$token}";
    }
}
