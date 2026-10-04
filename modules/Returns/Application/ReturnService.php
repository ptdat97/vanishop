<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Contracts\InventoryReturns;
use Modules\Ordering\Contracts\Data\OrderLineData;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Payment\Contracts\Payments;
use Modules\Returns\Contracts\Data\ReturnContext;
use Modules\Returns\Contracts\Data\ReturnView;
use Modules\Returns\Contracts\ReturnPolicy;
use Modules\Returns\Contracts\ReturnRejected;
use Modules\Returns\Contracts\Returns;
use Modules\Returns\Domain\RefundCalculator;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Events\ReturnRequested;
use Modules\Returns\Events\ReturnResolved;
use Modules\Returns\Persistence\Models\ReturnLine;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Modules\Tenancy\Contracts\Settings;

/**
 * Invariant: tổng số lượng trả (yêu cầu còn mở + đã hoàn tất) của một dòng ≤ số đã giao — khoá dòng đơn khi tạo.
 * Tiền hoàn tính từ thành tiền dòng đã phân bổ giảm giá (không hoàn phí giao). Hoàn tiền qua Payment (≤ đã thu).
 */
final class ReturnService implements Returns
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly ShipmentReader $shipments,
        private readonly InventoryReturns $inventory,
        private readonly Payments $payments,
        private readonly AuditLogger $audit,
        private readonly CurrentContext $context,
        private readonly Extensions $extensions,
        private readonly Settings $settings,
    ) {}

    public function request(int $orderId, array $lines, string $reasonCode, ?string $note, string $source): ReturnView
    {
        $lines = array_filter($lines, fn (int $quantity): bool => $quantity > 0);
        if ($lines === []) {
            throw ReturnRejected::quantityExceeded(0, 0);
        }

        $return = DB::transaction(function () use ($orderId, $lines, $reasonCode, $note, $source): ReturnRequest {
            $this->transitions->lock($orderId);
            $order = $this->orders->find($orderId) ?? throw ReturnRejected::notEligible('not_delivered');
            [$delivered, $deliveredAt] = $this->delivered($orderId);

            $decision = $this->policy()->evaluate(new ReturnContext($orderId, $lines, $reasonCode, $deliveredAt, now()->toDateTimeImmutable(), $source));
            if (! $decision->eligible) {
                throw ReturnRejected::notEligible((string) $decision->reason);
            }

            $orderLines = collect($this->orders->lines($orderId))->keyBy('id');
            $taken = $this->takenQuantities($orderId);
            $count = ReturnRequest::query()->where('order_id', $orderId)->count();

            $return = ReturnRequest::query()->create([
                'public_id' => (string) Str::ulid(), 'number' => $order->number.'-R'.($count + 1), 'order_id' => $orderId,
                'status' => ReturnStatus::Requested, 'reason_code' => $reasonCode, 'customer_note' => $note === null ? null : mb_substr(trim($note), 0, 500),
                'source' => $source, 'refund_amount' => 0, 'currency_code' => $order->currencyCode,
            ]);

            $total = 0;
            foreach ($lines as $lineId => $quantity) {
                /** @var OrderLineData|null $line */
                $line = $orderLines->get($lineId);
                $allowed = ($delivered[$lineId] ?? 0) - ($taken[$lineId] ?? 0);
                if ($line === null || $quantity > $allowed) {
                    throw ReturnRejected::quantityExceeded((int) $lineId, max(0, $allowed));
                }

                $refund = RefundCalculator::forUnits(Money::of($line->totalAmount, $order->currencyCode), $line->quantity, $taken[$lineId] ?? 0, $quantity);
                ReturnLine::query()->create(['return_request_id' => $return->id, 'order_line_id' => $lineId, 'variant_id' => $line->variantId, 'quantity' => $quantity, 'refund_amount' => $refund->amount]);
                $total += $refund->amount;
            }

            $return->update(['refund_amount' => $total]);
            $this->event($return, null, ReturnStatus::Requested, $reasonCode, $source);
            if ($decision->autoApprove) {
                $this->move($return, ReturnStatus::Approved, 'auto_approved', 'system');
            }
            $this->syncOrder($orderId);
            event(new ReturnRequested($return->id, $orderId, $return->number, $source, $return->public_id));

            return $return;
        });

        return $this->view($return->fresh());
    }

    public function forOrder(int $orderId): array
    {
        return ReturnRequest::query()->where('order_id', $orderId)->orderBy('id')->get()->map(fn (ReturnRequest $return): ReturnView => $this->view($return))->all();
    }

    public function returnable(int $orderId): array
    {
        [$delivered, $deliveredAt] = $this->delivered($orderId);
        $taken = $this->takenQuantities($orderId);
        $lines = [];
        foreach ($delivered as $lineId => $quantity) {
            $lines[$lineId] = max(0, $quantity - ($taken[$lineId] ?? 0));
        }

        $days = (int) config('vanishop.fulfillment.return_window_days', 7);

        return ['lines' => $lines, 'deadline' => $deliveredAt?->modify("+{$days} days")->format(DATE_ATOM)];
    }

    public function cancel(int $orderId, string $returnPublicId, string $source): ReturnView
    {
        $return = ReturnRequest::query()->where('order_id', $orderId)->where('public_id', $returnPublicId)->firstOrFail();
        $this->transition($return->id, ReturnStatus::Cancelled, "cancelled_by_{$source}", $source);

        return $this->view($return->fresh());
    }

    /**
     * Thao tác nhân viên: duyệt, từ chối, đang gửi về.
     */
    public function transition(int $returnId, ReturnStatus $to, ?string $note, string $source, ?int $expectedLockVersion = null): void
    {
        DB::transaction(function () use ($returnId, $to, $note, $source, $expectedLockVersion): void {
            $return = ReturnRequest::query()->whereKey($returnId)->lockForUpdate()->firstOrFail();
            if ($expectedLockVersion !== null && $return->lock_version !== $expectedLockVersion) {
                throw ReturnRejected::stale();
            }
            if ($to === ReturnStatus::Cancelled && $source === 'customer' && $return->status !== ReturnStatus::Requested) {
                throw ReturnRejected::invalidTransition($return->status->value, $to->value);
            }

            $this->move($return, $to, $note, $source);
            $this->syncOrder($return->order_id);
            $this->audit->record("return.{$to->value}", 'return_request', $return->id, ['number' => $return->number, 'note' => $note]);
        });
    }

    /**
     * Nhận hàng trả: ghi tình trạng từng dòng; dòng bán được thì nhập lại kho (location của vận đơn đã giao).
     *
     * @param  array<int, string>  $conditions  return_line_id => sellable|damaged
     */
    public function receive(int $returnId, array $conditions, ?string $note): void
    {
        DB::transaction(function () use ($returnId, $conditions, $note): void {
            $return = ReturnRequest::query()->with('lines')->whereKey($returnId)->lockForUpdate()->firstOrFail();
            if (! $return->status->canMoveTo(ReturnStatus::Received)) {
                throw ReturnRejected::invalidTransition($return->status->value, ReturnStatus::Received->value);
            }

            // Khoá tình trạng = id dòng trả hàng. Khoá lạ bị từ chối: không âm thầm coi là "bán được" và nhập kho hàng hỏng.
            $unknown = array_diff(array_keys($conditions), $return->lines->pluck('id')->all());
            if ($unknown !== []) {
                throw ReturnRejected::unknownLines(array_values($unknown));
            }

            $locations = $this->shipmentLocations($return->order_id);
            foreach ($return->lines as $line) {
                $condition = ($conditions[$line->id] ?? 'sellable') === 'damaged' ? 'damaged' : 'sellable';
                $location = $locations[$line->order_line_id] ?? null;
                $line->update(['condition' => $condition, 'restock_location_id' => $condition === 'sellable' ? $location : null]);

                if ($condition === 'sellable' && $location !== null) {
                    $this->inventory->restock($location, $line->variant_id, $line->quantity, 'customer_return', "return:{$return->public_id}:{$line->id}");
                }
            }

            $this->move($return, ReturnStatus::Received, $note, 'staff');
            $this->syncOrder($return->order_id);
            $this->audit->record('return.received', 'return_request', $return->id, ['conditions' => $conditions]);
        });
    }

    /**
     * Hoàn tất: hoàn tiền (mặc định toàn bộ số tính được; có thể trừ phí hư hỏng) qua Payment.
     */
    public function resolve(int $returnId, ?int $amount, ?string $note): void
    {
        $return = ReturnRequest::query()->findOrFail($returnId);
        $amount ??= $return->refund_amount;
        if ($amount < 0 || $amount > $return->refund_amount) {
            throw ReturnRejected::refundExceeds($return->refund_amount);
        }

        DB::transaction(function () use ($returnId, $amount, $note): void {
            $return = ReturnRequest::query()->whereKey($returnId)->lockForUpdate()->firstOrFail();
            if (! $return->status->canMoveTo(ReturnStatus::Resolved)) {
                throw ReturnRejected::invalidTransition($return->status->value, ReturnStatus::Resolved->value);
            }

            if ($amount > 0) {
                $this->payments->refundOrder($return->order_id, Money::of($amount, $return->currency_code), "return:{$return->number}", "return:{$return->public_id}");
            }

            $return->update(['refunded_amount' => $amount, 'resolved_at' => now()]);
            $this->move($return, ReturnStatus::Resolved, $note, 'staff');
            $this->syncOrder($return->order_id);
            $this->audit->record('return.resolved', 'return_request', $return->id, ['refunded' => $amount]);
            event(new ReturnResolved($return->id, $return->order_id, $amount, $return->public_id, $return->number));
        });
    }

    public function view(ReturnRequest $return): ReturnView
    {
        $orderLines = collect($this->orders->lines($return->order_id))->keyBy('id');

        return new ReturnView(
            id: $return->id,
            publicId: $return->public_id,
            number: $return->number,
            orderId: $return->order_id,
            status: $return->status->value,
            reasonCode: $return->reason_code,
            customerNote: $return->customer_note,
            source: (string) $return->source,
            refundAmount: $return->refund_amount,
            refundedAmount: $return->refunded_amount,
            currencyCode: $return->currency_code,
            createdAt: $return->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '',
            lines: $return->lines()->get()->map(fn (ReturnLine $line): array => [
                'id' => $line->id, 'order_line_id' => $line->order_line_id, 'sku' => $orderLines[$line->order_line_id]->sku ?? '',
                'name' => $orderLines[$line->order_line_id]->productName ?? '', 'quantity' => $line->quantity,
                'refund_amount' => $line->refund_amount, 'condition' => $line->condition,
            ])->all(),
            events: DB::table('return_events')->where('return_request_id', $return->id)->orderBy('id')->get()->map(fn (object $event): array => [
                'from' => $event->from_status, 'to' => $event->to_status, 'note' => $event->note, 'source' => $event->source,
                'at' => Carbon::parse((string) $event->created_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            ])->all(),
            lockVersion: $return->lock_version,
        );
    }

    private function move(ReturnRequest $return, ReturnStatus $to, ?string $note, string $source): void
    {
        $from = $return->status;
        if (! $from->canMoveTo($to)) {
            throw ReturnRejected::invalidTransition($from->value, $to->value);
        }

        $return->update(['status' => $to, 'lock_version' => $return->lock_version + 1]);
        $this->event($return, $from, $to, $note, $source);
    }

    private function event(ReturnRequest $return, ?ReturnStatus $from, ReturnStatus $to, ?string $note, string $source): void
    {
        $actor = $this->context->has() ? $this->context->actor() : null;
        DB::table('return_events')->insert([
            'return_request_id' => $return->id, 'from_status' => $from?->value, 'to_status' => $to->value,
            'note' => $note === null ? null : mb_substr($note, 0, 255), 'actor_type' => $actor?->type->value, 'actor_id' => $actor?->id,
            'source' => $source, 'created_at' => now(),
        ]);
    }

    /**
     * return_status của đơn: có yêu cầu mở → requested/in_progress; không thì theo số lượng đã trả xong.
     */
    private function syncOrder(int $orderId): void
    {
        $returns = ReturnRequest::query()->with('lines')->where('order_id', $orderId)->get();
        $open = $returns->filter(fn (ReturnRequest $return): bool => $return->status->isOpen());
        [$delivered] = $this->delivered($orderId);
        $resolvedQuantity = $returns->where('status', ReturnStatus::Resolved)->sum(fn (ReturnRequest $return): int => $return->lines->sum('quantity'));

        $status = match (true) {
            $open->isNotEmpty() => $open->every(fn (ReturnRequest $return): bool => $return->status === ReturnStatus::Requested) ? 'requested' : 'in_progress',
            $resolvedQuantity === 0 => 'none',
            $resolvedQuantity >= array_sum($delivered) => 'returned',
            default => 'partially_returned',
        };

        $this->transitions->setReturnStatus($orderId, $status, 'returns_changed', 'system');
    }

    /**
     * @return array<int, int> order_line_id => số lượng đang/đã trả (yêu cầu mở hoặc đã hoàn tất)
     */
    private function takenQuantities(int $orderId): array
    {
        $taken = [];
        foreach (ReturnRequest::query()->with('lines')->where('order_id', $orderId)->get() as $return) {
            if (! $return->status->countsTowardsLimit()) {
                continue;
            }
            foreach ($return->lines as $line) {
                $taken[$line->order_line_id] = ($taken[$line->order_line_id] ?? 0) + $line->quantity;
            }
        }

        return $taken;
    }

    /**
     * @return array{0: array<int, int>, 1: ?DateTimeImmutable} số đã giao theo dòng + thời điểm giao gần nhất
     */
    private function delivered(int $orderId): array
    {
        $quantities = [];
        $latest = null;
        foreach ($this->shipments->forOrder($orderId) as $shipment) {
            if ($shipment->status !== 'delivered') {
                continue;
            }
            foreach ($shipment->lines as $line) {
                $quantities[$line['order_line_id']] = ($quantities[$line['order_line_id']] ?? 0) + $line['quantity'];
            }
            $at = $shipment->deliveredAt === null ? null : new DateTimeImmutable($shipment->deliveredAt);
            $latest = $latest === null || ($at !== null && $at > $latest) ? $at : $latest;
        }

        return [$quantities, $latest];
    }

    /**
     * @return array<int, int> order_line_id => location của vận đơn đã giao dòng đó
     */
    private function shipmentLocations(int $orderId): array
    {
        $locations = [];
        foreach ($this->shipments->forOrder($orderId) as $shipment) {
            if ($shipment->status === 'delivered') {
                foreach ($shipment->lines as $line) {
                    $locations[$line['order_line_id']] ??= $shipment->locationId;
                }
            }
        }

        return $locations;
    }

    /**
     * Chính sách đổi trả theo cấu hình `core.returns.policy` của cửa hàng (mặc định VANI_RETURN_POLICY).
     */
    private function policy(): ReturnPolicy
    {
        $default = (string) config('vanishop.returns.policy', 'days_window');
        $code = (string) $this->settings->get('core', 'returns.policy', $default);
        $policy = $this->extensions->select(ReturnPolicy::TAG, $code, $default);

        return $policy instanceof ReturnPolicy ? $policy : throw new \RuntimeException("ReturnPolicy [{$code}] chưa được đăng ký.");
    }
}
