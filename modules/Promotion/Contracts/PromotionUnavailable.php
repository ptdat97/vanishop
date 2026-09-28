<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Khuyến mãi/voucher hết lượt hoặc hết ngân sách ngay lúc ghi nhận (PlaceOrder) → đơn rollback.
 */
final class PromotionUnavailable extends BusinessRuleViolation
{
    private function __construct(private readonly string $code_, string $message, private readonly array $details_)
    {
        parent::__construct($message);
    }

    public static function voucherExhausted(string $code): self
    {
        return new self('promotion.voucher_exhausted', __('promotion::messages.voucher_exhausted', ['code' => $code]), ['voucher' => $code]);
    }

    public static function limitReached(string $name): self
    {
        return new self('promotion.limit_reached', __('promotion::messages.limit_reached', ['name' => $name]), []);
    }

    public function errorCode(): string
    {
        return $this->code_;
    }

    public function details(): array
    {
        return $this->details_;
    }
}
