<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Support\Carbon;
use Modules\Promotion\Contracts\Data\VoucherData;
use Modules\Promotion\Contracts\Vouchers;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\Voucher;

final class VoucherService implements Vouchers
{
    public function __construct(private readonly PromotionService $promotions) {}

    public function create(int $promotionId, string $code, ?int $usageLimit = null, ?int $expiresAt = null, ?int $customerId = null): int
    {
        $codes = $this->promotions->createVouchers(Promotion::query()->findOrFail($promotionId), $code, null, 1, $usageLimit, $this->time($expiresAt), $customerId);

        return (int) Voucher::query()->where('code', $codes[0])->value('id');
    }

    public function generate(int $promotionId, string $prefix, int $count, ?int $usageLimit = null, ?int $expiresAt = null, ?int $customerId = null): array
    {
        return $this->promotions->createVouchers(Promotion::query()->findOrFail($promotionId), null, $prefix, $count, $usageLimit, $this->time($expiresAt), $customerId);
    }

    public function ofPromotion(int $promotionId): array
    {
        return Voucher::query()->where('promotion_id', $promotionId)->orderBy('id')->get()->map($this->data(...))->all();
    }

    public function forCustomer(int $customerId): array
    {
        return Voucher::query()->where('customer_id', $customerId)->orderByDesc('id')->get()->map($this->data(...))->all();
    }

    public function setActive(int $voucherId, bool $active): void
    {
        $this->promotions->setVoucherStatus(Voucher::query()->findOrFail($voucherId), $active ? 'active' : 'inactive');
    }

    public function remove(int $voucherId): void
    {
        $voucher = Voucher::query()->find($voucherId);
        if ($voucher !== null) {
            $this->promotions->removeVoucher($voucher);
        }
    }

    private function data(Voucher $voucher): VoucherData
    {
        return new VoucherData($voucher->id, $voucher->promotion_id, $voucher->code, $voucher->customer_id, $voucher->usage_limit,
            $voucher->used_count, $voucher->expires_at?->getTimestamp(), $voucher->status === 'active');
    }

    private function time(?int $timestamp): ?string
    {
        return $timestamp === null ? null : Carbon::createFromTimestamp($timestamp)->toDateTimeString();
    }
}
