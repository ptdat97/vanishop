<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Checkout\Contracts\Data\ReplacementLine;
use Modules\Checkout\Contracts\Data\ReplacementOrderRequest;
use Modules\Checkout\Contracts\ReplacementOrders;
use Modules\Checkout\Contracts\ReplacementUnavailable;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Payment\Contracts\Payments;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Returns\Contracts\ReturnRejected;
use Modules\Returns\Domain\ExchangeValuation;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Events\ReturnResolved;
use Modules\Returns\Persistence\Models\ReturnLine;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Domain\Money\Money;

/**
 * Đổi hàng (docs/09-order/order.md §7.1): báo giá theo `ExchangeValuation` của Domain, hoàn tất bằng đơn thay thế (giữ
 * hàng, COD phần khách bù) + hoàn phần thừa trên đơn gốc; chặn đổi khác mẫu trên đơn thay thế.
 */
final class ExchangeResolution
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly Payments $payments,
        private readonly AuditLogger $audit,
        private readonly VariantDirectory $variants,
        private readonly PriceResolver $prices,
        private readonly ReplacementOrders $replacements,
        private readonly ReturnRecords $records,
    ) {}

    /**
     * Giá trị đổi hàng (order §7.1): cùng mẫu (đổi size/màu) giữ đúng đơn giá đã mua và không tính chênh; khác mẫu tính
     * theo giá hiện tại, bù trừ với số tiền khách đã trả cho các món trả của nhóm này — khách bù phần thiếu (COD trên đơn
     * thay thế) hoặc được hoàn phần thừa.
     *
     * @return array{lines: list<array{return_line_id: int, variant_id: int, sku: string, quantity: int, unit_amount: int, credit: int, same_product: bool}>, payable: int, refund: int}
     */
    public function exchangeQuote(int $returnId): array
    {
        $return = ReturnRequest::query()->with('lines')->findOrFail($returnId);
        $orderLines = collect($this->orders->lines($return->order_id))->keyBy('id');
        $variants = $this->variants->find(array_values(array_unique([...$return->lines->pluck('variant_id')->all(), ...$return->lines->pluck('exchange_variant_id')->filter()->all()])));

        $other = $return->lines->filter(fn (ReturnLine $line): bool => ($variants[$line->exchange_variant_id]->productId ?? null) !== ($variants[$line->variant_id]->productId ?? -1));
        $prices = $other->isEmpty() ? [] : $this->prices->forVariants($other->pluck('exchange_variant_id')->unique()->values()->all(), new PricingContext(now()->getTimestamp()));

        $values = [];
        $result = [];
        foreach ($return->lines as $line) {
            $variant = $variants[$line->exchange_variant_id] ?? throw ReturnRejected::exchangeInvalid('variant');
            $same = $other->doesntContain('id', $line->id);
            $unit = $same ? (int) ($orderLines[$line->order_line_id]->unitAmount ?? 0) : ($prices[$line->exchange_variant_id]->amount->amount ?? throw ReturnRejected::exchangeUnavailable(__('returns::messages.exchange_invalid.variant')));
            $values[$line->id] = ['quantity' => (int) $line->quantity, 'unit_amount' => $unit, 'same_product' => $same, 'paid' => (int) $line->refund_amount];
            $result[$line->id] = ['return_line_id' => $line->id, 'variant_id' => $variant->id, 'sku' => $variant->sku, 'quantity' => $line->quantity, 'unit_amount' => $unit, 'credit' => 0, 'same_product' => $same];
        }
        $quote = ExchangeValuation::quote($values);
        foreach ($quote['credits'] as $lineId => $credit) {
            $result[$lineId]['credit'] = $credit;
        }

        return ['lines' => array_values($result), 'payable' => $quote['payable'], 'refund' => $quote['refund']];
    }

    /**
     * Đơn thay thế có dòng 0đ (giá trị đã bù từ đơn gốc): chỉ cho đổi tiếp size/màu cùng mẫu. Trả hoàn tiền / đổi mẫu khác
     * sẽ tính sai vì tiền thật nằm ở đơn gốc — CSKH xử lý trên đơn gốc.
     *
     * @param  array<int, int>  $lines
     * @param  array<int, int>  $exchanges
     */
    public function guardReplacementOrder(int $orderId, array $lines, array $exchanges): void
    {
        if ($exchanges === []) {
            throw ReturnRejected::notEligible('exchange_order');
        }
        $orderLines = collect($this->orders->lines($orderId))->keyBy('id');
        $variants = $this->variants->find(array_values(array_unique([...array_values($exchanges), ...$orderLines->only(array_keys($lines))->pluck('variantId')->all()])));
        foreach ($exchanges as $lineId => $variantId) {
            $original = $variants[$orderLines[$lineId]->variantId ?? 0] ?? null;
            if ($original === null || ($variants[$variantId] ?? null)?->productId !== $original->productId) {
                throw ReturnRejected::notEligible('exchange_order');
            }
        }
    }

    /**
     * Hoàn tất đổi hàng (sau khi đã nhận hàng trả): tạo đơn thay thế (giữ hàng, COD phần khách bù), hoàn phần thừa trên
     * đơn gốc. Hết hàng thay thế → từ chối, yêu cầu giữ nguyên trạng thái để nhân viên xử lý.
     */
    public function resolve(int $returnId, ?string $note): void
    {
        $quote = $this->exchangeQuote($returnId);

        DB::transaction(function () use ($returnId, $note, $quote): void {
            $return = ReturnRequest::query()->whereKey($returnId)->lockForUpdate()->firstOrFail();
            if (! $return->status->canMoveTo(ReturnStatus::Resolved)) {
                throw ReturnRejected::invalidTransition($return->status->value, ReturnStatus::Resolved->value);
            }

            try {
                $replacement = $this->replacements->place(new ReplacementOrderRequest($return->order_id, $return->number, array_map(
                    fn (array $line): ReplacementLine => new ReplacementLine($line['variant_id'], $line['quantity'], $line['unit_amount'], $line['credit']),
                    $quote['lines'],
                )));
            } catch (ReplacementUnavailable $exception) {
                throw ReturnRejected::exchangeUnavailable($exception->getMessage());
            }

            if ($quote['refund'] > 0) {
                $this->payments->refundOrder($return->order_id, Money::of($quote['refund'], $return->currency_code), "return:{$return->number}", "return:{$return->public_id}");
            }

            $return->update(['refunded_amount' => $quote['refund'], 'replacement_order_id' => $replacement->id, 'resolved_at' => now()]);
            $this->records->move($return, ReturnStatus::Resolved, $note ?? "exchange:{$replacement->number}", 'staff');
            $this->records->syncOrder($return->order_id);
            $this->audit->record('return.exchanged', 'return_request', $return->id, ['replacement' => $replacement->number, 'payable' => $quote['payable'], 'refunded' => $quote['refund']]);
            event(new ReturnResolved($return->id, $return->order_id, $quote['refund'], $return->public_id, $return->number, $replacement->id));
        });
    }
}
