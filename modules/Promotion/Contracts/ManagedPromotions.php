<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\ManagedPromotion;
use Modules\Promotion\Contracts\Data\ManagedPromotionUsage;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract: plugin đồng bộ nội dung của mình thành khuyến mãi của Core, khoá theo (plugin, khoá của plugin).
 * Admin → Khuyến mãi hiện khuyến mãi này ở chế độ chỉ xem, kèm liên kết sang màn hình của plugin.
 * Validate rule/action như khi lưu ở Admin (lỗi → ValidationException với khoá `action_config`, `rules.N.*`).
 */
#[PluginApi]
interface ManagedPromotions
{
    /** Tạo hoặc cập nhật; trả id khuyến mãi. */
    public function sync(string $owner, string $key, ManagedPromotion $promotion): int;

    /** Gỡ: xoá khi chưa có lượt dùng, đã dùng thì chuyển sang ngừng để giữ lịch sử đơn. */
    public function remove(string $owner, string $key): void;

    /**
     * @return array<int|string, int> khoá của plugin => id khuyến mãi (khoá số thành int theo quy tắc khoá mảng của PHP)
     */
    public function ids(string $owner): array;

    /**
     * Số liệu dùng (0.5.109) cho danh sách của plugin.
     *
     * @return array<int|string, ManagedPromotionUsage> khoá của plugin => số liệu
     */
    public function usage(string $owner): array;
}
