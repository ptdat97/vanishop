<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use Modules\Pricing\Contracts\Data\PriceQuotaRule;
use Modules\Pricing\Contracts\Data\PriceQuotaStatus;
use Modules\Pricing\Contracts\Data\QuotaLine;
use Modules\Pricing\Contracts\Data\QuotaViolation;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.5.117): suất giá của bảng giá — flash sale 100 suất, mỗi khách tối đa 2. Hết suất (hoặc khách đã dùng hết
 * lượt) → mức giá đó không tham gia giải giá (giá về giá thường). Checkout kiểm `check` trước khi đặt đơn, `consume` trong
 * transaction đặt hàng; huỷ đơn / huỷ một phần hoàn lại (Pricing nghe `OrderCancelled`, `OrderLinesCancelled`).
 */
#[PluginApi]
interface PriceQuotas
{
    /**
     * Thay toàn bộ suất của bảng giá; suất cùng (phạm vi, id) giữ số đã bán.
     *
     * @param  list<PriceQuotaRule>  $rules
     */
    public function define(int $priceListId, array $rules): void;

    /**
     * @return list<PriceQuotaStatus>
     */
    public function status(int $priceListId): array;

    /**
     * Số lượng trong giỏ vượt suất còn lại / giới hạn mỗi khách (khách null = chưa biết, bỏ qua giới hạn mỗi khách).
     *
     * @param  list<QuotaLine>  $lines
     * @return list<QuotaViolation>
     */
    public function check(array $lines, ?int $customerId): array;

    /**
     * Trừ suất (UPDATE có điều kiện) — phải chạy TRONG transaction đặt hàng; hết suất → PriceQuotaExceeded.
     *
     * @param  list<QuotaLine>  $lines
     */
    public function consume(int $orderId, ?int $customerId, array $lines): void;

    /** Huỷ đơn: hoàn mọi suất của đơn (idempotent). */
    public function revert(int $orderId): void;

    /**
     * Huỷ một phần: hoàn suất theo số lượng huỷ của từng variant.
     *
     * @param  array<int, int>  $quantities  variant id => số lượng huỷ
     */
    public function release(int $orderId, array $quantities): void;
}
