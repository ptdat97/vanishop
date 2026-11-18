<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Persistence\Models\ReturnAttachment;
use Modules\Returns\Persistence\Models\ReturnRequest;

/**
 * Xoá ảnh khách gửi kèm yêu cầu đổi trả (dữ liệu cá nhân — backlog L16, Owner 2026-11-18): ảnh của yêu cầu đã đóng
 * (hoàn tất / từ chối / huỷ) quá hạn lưu (mặc định 90 ngày, `vanishop.returns.attachments_retention_days`), và mọi ảnh
 * của khách bị ẩn danh hoá. Xoá cả file trên disk lẫn dòng `return_attachments`; yêu cầu đổi trả giữ nguyên.
 */
final class ReturnAttachmentPurge
{
    private const CLOSED = [ReturnStatus::Resolved, ReturnStatus::Rejected, ReturnStatus::Cancelled];

    /** Yêu cầu đóng trước `$closedBefore` (thời điểm hoàn tất, hoặc lần cập nhật cuối với yêu cầu từ chối/huỷ). */
    public function closedBefore(DateTimeInterface $closedBefore): int
    {
        $returnIds = ReturnRequest::query()
            ->whereIn('status', array_map(fn (ReturnStatus $status): string => $status->value, self::CLOSED))
            ->whereRaw('COALESCE(resolved_at, updated_at) < ?', [$closedBefore])
            ->whereIn('id', ReturnAttachment::query()->select('return_request_id'))
            ->pluck('id')->all();

        return $this->purge($returnIds);
    }

    /**
     * @param  list<int>  $orderIds
     */
    public function forOrders(array $orderIds): int
    {
        return $orderIds === [] ? 0 : $this->purge(ReturnRequest::query()->whereIn('order_id', $orderIds)->pluck('id')->all());
    }

    /**
     * @param  array<int, mixed>  $returnIds
     */
    private function purge(array $returnIds): int
    {
        $deleted = 0;
        ReturnAttachment::query()->whereIn('return_request_id', $returnIds)->chunkById(200, function ($attachments) use (&$deleted): void {
            foreach ($attachments as $attachment) {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
                $deleted++;
            }
        });

        return $deleted;
    }
}
