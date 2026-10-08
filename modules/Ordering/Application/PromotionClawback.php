<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Persistence\Models\OrderLine;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionLine;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Shared\Domain\Money\Money;

/**
 * Khách yêu cầu bớt hàng (huỷ một phần, order §2.1): phần giảm giá khuyến mãi mà phần hàng còn lại không còn đủ điều
 * kiện (vd. dưới ngưỡng) được thu hồi. Huỷ do lỗi shop thì không gọi lớp này (giữ nguyên ưu đãi cho khách).
 *
 * - Chỉ thu hồi, không bao giờ tăng giảm giá; tổng thu hồi ≤ số tiền của phần vừa huỷ, nên tổng đơn sau huỷ không vượt
 *   tổng trước đó (khách không phải trả nhiều hơn số đã đồng ý).
 * - Cần chi tiết giảm giá theo từng khuyến mãi trên dòng (`order_lines.meta.promotions`, ghi từ 0.3.31); đơn cũ hơn
 *   không có → không thu hồi.
 * - `meta.promotions` lưu số tiền ứng với `meta.promotions_basis_qty` đơn vị; phần còn lại theo tỷ lệ số lượng hiện tại.
 */
final class PromotionClawback
{
    public function __construct(private readonly PromotionEngine $promotions) {}

    /**
     * @param  Collection<int, OrderLine>  $lines  mọi dòng của đơn, ĐÃ trừ phần vừa huỷ (đang khoá trong transaction)
     * @return array{total: int, lines: array<int, int>, promotions: array<int, int>, detail: array<int, array<int, int>>}
     *                                                                                                                     số tiền thu hồi: tổng, theo dòng, theo khuyến mãi, chi tiết dòng => (khuyến mãi => số tiền)
     */
    public function calculate(Order $order, Collection $lines, int $cancelledTotal): array
    {
        $none = ['total' => 0, 'lines' => [], 'promotions' => [], 'detail' => []];
        $promotionIds = DB::table('order_adjustments')->where('order_id', $order->id)->where('type', 'promotion')->orderBy('id')->pluck('meta')
            ->map(fn ($meta): int => (int) (json_decode((string) $meta, true)['promotion_id'] ?? 0))->filter()->unique()->values()->all();
        if ($promotionIds === [] || $cancelledTotal <= 0) {
            return $none;
        }

        $currency = $order->currency_code;
        $active = $lines->filter(fn (OrderLine $line): bool => $line->quantity > 0);
        $rechecked = $this->promotions->recheck(new PromotionContext(
            $order->customer_id,
            $currency,
            $active->map(fn (OrderLine $line): PromotionLine => new PromotionLine(
                $line->id, $line->brand_id, (int) ($line->meta['style_id'] ?? 0), $line->quantity, Money::of($line->unit_amount, $currency), Money::of($line->subtotal_amount, $currency), $line->variant_id,
            ))->values()->all(),
            [],
            $order->placed_at->getTimestamp(),
        ), $promotionIds);

        $byLine = [];
        $byPromotion = [];
        foreach ($active as $line) {
            $claw = 0;
            foreach ($this->retained($line) as $promotionId => $retained) {
                if (! array_key_exists($promotionId, $rechecked)) {
                    continue; // không kiểm tra lại được → giữ
                }
                $now = ($rechecked[$promotionId][$line->id] ?? Money::zero($currency))->amount;
                $amount = min(max(0, $retained - $now), $line->discount_amount - $claw);
                if ($amount > 0) {
                    $claw += $amount;
                    $byPromotion[$promotionId][$line->id] = $amount;
                }
            }
            if ($claw > 0) {
                $byLine[$line->id] = $claw;
            }
        }

        $total = array_sum($byLine);
        if ($total === 0) {
            return $none;
        }
        if ($total > $cancelledTotal) {
            [$byLine, $byPromotion] = $this->scaleDown($byLine, $byPromotion, $cancelledTotal);
            $total = $cancelledTotal;
        }

        $detail = [];
        foreach ($byPromotion as $promotionId => $perLine) {
            foreach (array_filter($perLine) as $lineId => $amount) {
                $detail[$lineId][$promotionId] = $amount;
            }
        }

        return [
            'total' => $total,
            'lines' => $byLine,
            'promotions' => array_filter(array_map(fn (array $perLine): int => array_sum($perLine), $byPromotion)),
            'detail' => $detail,
        ];
    }

    /**
     * Ghi lại giảm giá còn lại theo từng khuyến mãi trên dòng sau khi thu hồi (để lần huỷ sau tính đúng).
     *
     * @param  array<int, int>  $clawedByPromotion  promotion id => số tiền thu hồi trên dòng này
     * @return array<string, mixed> meta mới của dòng
     */
    public function lineMetaAfter(OrderLine $line, array $clawedByPromotion): array
    {
        $current = $this->retained($line);
        foreach ($clawedByPromotion as $promotionId => $amount) {
            $current[$promotionId] = max(0, ($current[$promotionId] ?? 0) - $amount);
        }

        return [...(array) $line->meta, 'promotions' => $current, 'promotions_basis_qty' => $line->quantity];
    }

    /**
     * Giảm giá còn lại của từng khuyến mãi trên cả đơn (sau khi đã cập nhật dòng) — để Promotion cập nhật lượt dùng/ngân
     * sách. Rỗng khi đơn không có chi tiết theo dòng (đặt trước 0.3.31).
     *
     * @param  Collection<int, OrderLine>  $lines
     * @return array<int, int> promotion id => số tiền
     */
    public function currentByPromotion(Collection $lines): array
    {
        $current = [];
        foreach ($lines as $line) {
            foreach ($this->retained($line) as $promotionId => $amount) {
                $current[$promotionId] = ($current[$promotionId] ?? 0) + $amount;
            }
        }

        return $current;
    }

    /**
     * Giảm giá của từng khuyến mãi ứng với số lượng hiện tại của dòng.
     *
     * @return array<int, int>
     */
    private function retained(OrderLine $line): array
    {
        $amounts = (array) ($line->meta['promotions'] ?? []);
        $basis = (int) ($line->meta['promotions_basis_qty'] ?? ($line->quantity + $line->cancelled_quantity));
        if ($basis <= 0) {
            return [];
        }

        $retained = [];
        foreach ($amounts as $promotionId => $amount) {
            $retained[(int) $promotionId] = intdiv((int) $amount * $line->quantity, $basis);
        }

        return $retained;
    }

    /**
     * Thu nhỏ theo tỷ lệ cho vừa trần; phần dư do làm tròn dồn vào các mục đầu.
     *
     * @param  array<int, int>  $byLine
     * @param  array<int, array<int, int>>  $byPromotion
     * @return array{0: array<int, int>, 1: array<int, array<int, int>>}
     */
    private function scaleDown(array $byLine, array $byPromotion, int $cap): array
    {
        $total = array_sum($byLine);
        $scaled = [];
        $entries = [];
        foreach ($byPromotion as $promotionId => $perLine) {
            foreach ($perLine as $lineId => $amount) {
                $value = intdiv($amount * $cap, $total);
                $scaled[$promotionId][$lineId] = $value;
                $entries[] = [$promotionId, $lineId, $amount - $value];
            }
        }
        $left = $cap - array_sum(array_map('array_sum', $scaled));
        foreach ($entries as [$promotionId, $lineId, $room]) {
            if ($left <= 0) {
                break;
            }
            $add = min($left, $room);
            $scaled[$promotionId][$lineId] += $add;
            $left -= $add;
        }

        $lines = [];
        foreach ($scaled as $perLine) {
            foreach ($perLine as $lineId => $amount) {
                $lines[$lineId] = ($lines[$lineId] ?? 0) + $amount;
            }
        }

        return [array_filter($lines), $scaled];
    }
}
