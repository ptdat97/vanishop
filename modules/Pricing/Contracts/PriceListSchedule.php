<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use DateTimeInterface;

/**
 * Service contract (0.3.36): module khác điều khiển lịch chạy của bảng giá — Promotion dùng cho campaign (campaign sở
 * hữu lịch, đồng bộ xuống bảng giá thành viên). Không áp cho bảng giá `base` (tắt là mất giá chung).
 */
interface PriceListSchedule
{
    /**
     * Bảng giá không phải `base` (sale, member) để gắn vào campaign.
     *
     * @return list<array{id: int, code: string, name: string, type: string, status: string}>
     */
    public function schedulable(): array;

    /**
     * Đặt khung giờ + bật/tắt. Ghi audit, phát PriceChanged.
     *
     * @throws \InvalidArgumentException bảng giá không tồn tại hoặc là `base`
     */
    public function schedule(int $priceListId, ?DateTimeInterface $startsAt, ?DateTimeInterface $endsAt, bool $active, string $reason): void;
}
