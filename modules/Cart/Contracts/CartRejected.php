<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Lỗi nghiệp vụ công khai của giỏ hàng. Mã lỗi ổn định: xem docs/06-api/api.md.
 */
final class CartRejected extends BusinessRuleViolation
{
    /**
     * @param  array<string, mixed>  $details
     */
    private function __construct(
        private readonly string $code_,
        private readonly int $status_,
        string $message,
        private readonly array $details_ = [],
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('cart.not_found', 404, __('cart::messages.not_found'));
    }

    public static function closed(): self
    {
        return new self('cart.closed', 409, __('cart::messages.closed'));
    }

    public static function lineNotFound(): self
    {
        return new self('cart.line_not_found', 404, __('cart::messages.line_not_found'));
    }

    public static function variantUnavailable(int $variantId): self
    {
        return new self('cart.variant_unavailable', 422, __('cart::messages.variant_unavailable'), ['variant_id' => $variantId]);
    }

    public static function insufficientStock(int $variantId): self
    {
        return new self('cart.insufficient_stock', 409, __('cart::messages.insufficient_stock'), ['variant_id' => $variantId]);
    }

    public static function quantity(string $reason, int $max): self
    {
        return new self("cart.{$reason}", 422, __("cart::messages.{$reason}", ['max' => $max]), ['max' => $max]);
    }

    public static function optionInvalid(string $plugin, string $message): self
    {
        return new self('cart.option_invalid', 422, $message, ['plugin' => $plugin]);
    }

    public static function optionUnknown(string $plugin): self
    {
        return new self('cart.option_unknown', 422, __('cart::messages.option_unknown'), ['plugin' => $plugin]);
    }

    public static function tooManyLines(int $max): self
    {
        return new self('cart.too_many_lines', 422, __('cart::messages.too_many_lines', ['max' => $max]), ['max' => $max]);
    }

    /**
     * @param  list<string>  $messages  lỗi do plugin trả về qua hook vani.cart.validate_line
     */
    public static function byRule(int $variantId, array $messages): self
    {
        return new self('cart.line_rejected', 422, $messages[0] ?? __('cart::messages.line_rejected'), ['variant_id' => $variantId, 'messages' => $messages]);
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
