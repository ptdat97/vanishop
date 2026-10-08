<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Modules\Ordering\Contracts\Data\PriceBreakdown;
use Modules\Ordering\Domain\LinePromotions;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Persistence\Models\OrderAdjustment;
use Modules\Ordering\Persistence\Models\OrderLine;

/**
 * Tách tầng giá của đơn từ snapshot (PriceBreakdown). Theo dòng: giá niêm yết = `compare_at` (nếu cao hơn giá bán) × số
 * lượng hiện tại; giảm giá bán = niêm yết − tạm tính; giảm giá của dòng chia theo khuyến mãi (`meta.promotions`), loại
 * mã giảm giá nếu adjustment `promotion` của khuyến mãi đó có voucher. Phần dư do làm tròn dồn vào khuyến mãi lớn nhất
 * của dòng; dòng không có chi tiết (đơn trước 0.3.31, đơn đổi hàng) → "giảm khác", trừ đơn thường cũ → khuyến mãi.
 */
final class PriceBreakdownCalculator
{
    public function for(Order $order): PriceBreakdown
    {
        $order->loadMissing(['lines', 'adjustments']);

        /** @var array<int, array{name: string, code: ?string, voucher: bool}> $known */
        $known = [];
        foreach ($order->adjustments as $adjustment) {
            /** @var OrderAdjustment $adjustment */
            $promotionId = (int) ($adjustment->meta['promotion_id'] ?? 0);
            if ($adjustment->type === 'promotion' && $promotionId > 0) {
                $known[$promotionId] = ['name' => $adjustment->label, 'code' => $adjustment->code, 'voucher' => ($adjustment->meta['voucher_id'] ?? null) !== null];
            }
        }

        $list = 0;
        $subtotal = 0;
        $other = 0;
        $byPromotion = [];
        $markdownByList = [];
        foreach ($order->lines as $line) {
            /** @var OrderLine $line */
            $unitList = $line->compare_at_amount !== null && $line->compare_at_amount > $line->unit_amount ? $line->compare_at_amount : $line->unit_amount;
            $lineList = max($unitList * $line->quantity, $line->subtotal_amount);
            $list += $lineList;
            $subtotal += $line->subtotal_amount;
            if ($lineList > $line->subtotal_amount && $line->price_list_code !== null) {
                $markdownByList[$line->price_list_code] = ($markdownByList[$line->price_list_code] ?? 0) + $lineList - $line->subtotal_amount;
            }

            if (! LinePromotions::known($line->meta)) {
                $other += $line->discount_amount;

                continue;
            }
            $shares = array_filter(LinePromotions::current($line->meta, $line->quantity, $line->cancelled_quantity));
            $residual = $line->discount_amount - array_sum($shares);
            if ($shares !== [] && $residual !== 0) {
                $largest = array_search(max($shares), $shares, true);
                $shares[$largest] += $residual;
                $residual = 0;
            }
            $other += $residual;
            foreach ($shares as $promotionId => $amount) {
                $byPromotion[$promotionId] = ($byPromotion[$promotionId] ?? 0) + $amount;
            }
        }

        // Đơn thường đặt trước 0.3.31 (không có chi tiết theo khuyến mãi): coi giảm giá là khuyến mãi, không phải "khác".
        $legacyPromotion = $order->source !== 'exchange' && $byPromotion === [] && $known !== [] ? $other : 0;
        $other -= $legacyPromotion;

        $promotions = [];
        $promotionDiscount = $legacyPromotion;
        $voucherDiscount = 0;
        foreach ($byPromotion as $promotionId => $amount) {
            $info = $known[$promotionId] ?? ['name' => "Khuyến mãi #{$promotionId}", 'code' => null, 'voucher' => false];
            $promotions[] = ['promotion_id' => $promotionId, ...$info, 'amount' => $amount];
            if ($info['voucher']) {
                $voucherDiscount += $amount;
            } else {
                $promotionDiscount += $amount;
            }
        }

        return new PriceBreakdown(
            listAmount: $list,
            markdown: $list - $subtotal,
            subtotal: $subtotal,
            promotionDiscount: $promotionDiscount,
            voucherDiscount: $voucherDiscount,
            otherDiscount: $other,
            shipping: $order->shipping_amount,
            taxIncluded: $order->tax_amount,
            total: $order->total_amount,
            promotions: $promotions,
            priceLists: array_map(fn (string $code, int $markdown): array => ['code' => $code, 'markdown' => $markdown], array_keys($markdownByList), $markdownByList),
        );
    }
}
