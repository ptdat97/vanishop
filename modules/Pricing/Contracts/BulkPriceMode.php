<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Extension point (tag `vani.pricing.bulk_modes`, EP v3 X4): cách tính thêm trong hộp thoại "Áp giá…" của bảng giá, ngoài
 * giảm %, giảm số tiền, đồng giá của Core — vd. "Giá vốn + %". Core lo phần còn lại: làm tròn, xem trước, cảnh báo, ghi
 * `PriceListService::setPrices` (lịch sử giá). Chỉ tính, không ghi; không I/O mạng chậm.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface BulkPriceMode
{
    public const TAG = 'vani.pricing.bulk_modes';

    public function code(): string;

    public function label(): string;

    /** Đơn vị/gợi ý cạnh ô nhập giá trị, vd. "% cộng thêm vào giá vốn". */
    public function hint(): string;

    /**
     * @param  list<int>  $variantIds
     * @param  array<int, int>  $listAmounts  giá niêm yết theo SKU (thiếu = chưa có)
     * @return array<int, int|null> giá mới theo SKU; null = không tính được (bỏ qua, báo "không tính được")
     */
    public function compute(array $variantIds, float $value, array $listAmounts): array;
}
