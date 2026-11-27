<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\Ineligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Shared\Contracts\PluginApi;

/**
 * Tuỳ chọn (0.5.109) cho {@see PromotionRule}: giải thích vì sao giỏ chưa đạt rule, để storefront báo khách
 * "Mua thêm 120.000đ để dùng mã" thay cho "không áp dụng". Core chỉ gọi khi voucher bị từ chối vì rule này trả rỗng.
 * Chỉ đọc, không I/O mạng.
 */
#[PluginApi]
interface ExplainsIneligibility
{
    /**
     * @param  array<string, mixed>  $config
     * @param  Eligibility  $candidates  dòng đang xét khi rule trả rỗng
     */
    public function explain(PromotionContext $context, array $config, Eligibility $candidates): ?Ineligibility;
}
