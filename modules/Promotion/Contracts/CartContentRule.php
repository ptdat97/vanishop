<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

/**
 * Đánh dấu (0.3.31) cho PromotionRule chỉ phụ thuộc vào dòng hàng của đơn/giỏ — ngưỡng tiền, số lượng, bộ sưu tập,
 * thương hiệu. Khi khách bớt hàng (huỷ một phần do khách yêu cầu), chỉ các rule này được kiểm tra lại
 * (`PromotionEngine::recheck`); rule phụ thuộc thứ khác (lịch sử khách, thời điểm, nguồn giới thiệu) coi như vẫn đạt
 * vì đã đạt lúc đặt hàng — vd. "đơn đầu tiên" chạy lại sau khi đơn đã tồn tại sẽ sai.
 */
interface CartContentRule extends PromotionRule {}
