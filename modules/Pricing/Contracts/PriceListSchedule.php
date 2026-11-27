<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use DateTimeInterface;
use Modules\Shared\Domain\Schedule\WeeklySchedule;

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
     * Bảng giá theo id (0.5.118) — mã và plugin quản lý (null = Admin).
     *
     * @param  list<int>  $ids
     * @return array<int, array{id: int, code: string, managed_by: ?string}>
     */
    public function describe(array $ids): array;

    /**
     * Đặt khung giờ + lịch lặp theo tuần (0.5.116, null = không lặp) + bật/tắt. Ghi audit, phát PriceChanged.
     *
     * @throws \InvalidArgumentException bảng giá không tồn tại hoặc là `base`
     */
    public function schedule(int $priceListId, ?DateTimeInterface $startsAt, ?DateTimeInterface $endsAt, bool $active, string $reason, ?WeeklySchedule $recurrence = null): void;

    /**
     * Mốc bắt đầu/kết thúc (kể cả mở/đóng của lịch lặp) sớm nhất sau `$now` của bảng giá đang bật (Unix timestamp) — giá hiệu lực đổi lúc đó. Cache trang
     * (ADR-051) dùng làm hạn của bản cache.
     */
    public function nextChangeAfter(int $now): ?int;
}
