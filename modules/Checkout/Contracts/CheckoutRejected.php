<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\CheckoutIssue;
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
     * Cửa hàng tắt đặt hàng không đăng nhập (`storefront.guest_checkout`): khách đăng nhập (OTP) rồi đặt lại.
     */
    public static function signInRequired(): self
    {
        return new self('checkout.sign_in_required', 403, __('checkout::messages.sign_in_required'));
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
