<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\VoucherData;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.5.109): tạo và đọc voucher của một khuyến mãi — mã dùng chung, sinh hàng loạt, mã riêng gán cho
 * một khách (ví voucher, mã chào mừng). Mã chuẩn hoá chữ hoa, bỏ khoảng trắng, duy nhất toàn cửa hàng. Có audit.
 * Mã trùng → ValidationException khoá `code`.
 */
#[PluginApi]
interface Vouchers
{
    /**
     * Tạo một mã cụ thể; trả id voucher.
     */
    public function create(int $promotionId, string $code, ?int $usageLimit = null, ?int $expiresAt = null, ?int $customerId = null): int;

    /**
     * Sinh `count` mã ngẫu nhiên `PREFIX` + 8 ký tự (1–5000 mã mỗi lần); trả các mã đã tạo.
     *
     * @return list<string>
     */
    public function generate(int $promotionId, string $prefix, int $count, ?int $usageLimit = null, ?int $expiresAt = null, ?int $customerId = null): array;

    /**
     * @return list<VoucherData>
     */
    public function ofPromotion(int $promotionId): array;

    /**
     * Mã riêng của khách (mọi trạng thái), mới nhất trước — nơi gọi tự lọc còn hạn/còn lượt để hiển thị.
     *
     * @return list<VoucherData>
     */
    public function forCustomer(int $customerId): array;

    public function setActive(int $voucherId, bool $active): void;

    /** Xoá khi chưa dùng; đã dùng thì chuyển sang tắt (giữ lịch sử đơn). */
    public function remove(int $voucherId): void;
}
