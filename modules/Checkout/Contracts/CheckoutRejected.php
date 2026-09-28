<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\CheckoutIssue;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Shared\Domain\BusinessRuleViolation;

final class CheckoutRejected extends BusinessRuleViolation
{
    /**
     * @param  array<string, mixed>  $details
     */
    private function __construct(private readonly string $code_, private readonly int $status_, string $message, private readonly array $details_ = [])
    {
        parent::__construct($message);
    }

    /**
     * @param  list<CheckoutIssue>  $issues
     */
    public static function invalid(array $issues): self
    {
        return new self('checkout.invalid', 422, $issues[0]->message ?? __('checkout::messages.invalid'), [
            'issues' => array_map(fn (CheckoutIssue $issue): array => ['code' => $issue->code, 'field' => $issue->field, 'message' => $issue->message], $issues),
        ]);
    }

    /**
     * Giá/khuyến mãi/phí đổi so với lúc khách xem → khách xác nhận lại với tổng mới.
     */
    public static function totalsChanged(int $expected, int $actual, string $currency): self
    {
        return new self('checkout.totals_changed', 409, __('checkout::messages.totals_changed'), ['expected_total' => $expected, 'total' => $actual, 'currency' => $currency]);
    }

    /**
     * @param  list<VoucherRejection>  $rejections
     */
    public static function vouchers(array $rejections): self
    {
        return new self('checkout.voucher_invalid', 422, __('checkout::messages.voucher_invalid', ['code' => $rejections[0]->code ?? '']), [
            'vouchers' => array_map(fn (VoucherRejection $rejection): array => ['code' => $rejection->code, 'reason' => $rejection->reason], $rejections),
        ]);
    }

    public function errorCode(): string
    {
        return $this->code_;
    }

    public function status(): int
    {
        return $this->status_;
    }

    public function details(): array
    {
        return $this->details_;
    }
}
