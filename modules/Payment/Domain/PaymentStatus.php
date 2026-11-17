<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

/**
 * Trạng thái khoản thanh toán và máy chuyển trạng thái (docs/10-payment/payment.md §2). Mọi thay đổi trạng thái đi qua
 * `transitionTo()` — chuyển sai bảng → InvalidPaymentTransition (lỗi lập trình, không phải lỗi của nhân viên).
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    /** Cổng đã giữ tiền, chờ thu (CapturesLater). */
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    /** @var array<string, list<string>> từ => các trạng thái được tới */
    private const TRANSITIONS = [
        'pending' => ['authorized', 'paid', 'failed', 'cancelled', 'expired'],
        // Lỗi/hết hạn vẫn có thể nhận tiền muộn (IPN đến sau, nhân viên xác nhận chuyển khoản).
        'failed' => ['paid', 'cancelled', 'expired'],
        'expired' => ['paid'],
        'authorized' => ['paid', 'cancelled'],
        'paid' => ['partially_refunded', 'refunded'],
        'partially_refunded' => ['partially_refunded', 'refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    /** Đang giữ tiền đã thu và còn hoàn tiếp được. */
    public function hasCollected(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded], true);
    }

    /** Đã từng thu (kể cả đã hoàn hết). */
    public function everCollected(): bool
    {
        return $this->hasCollected() || $this === self::Refunded;
    }

    /** Khách thử lại thanh toán (cùng cổng hoặc đổi cổng). */
    public function canRetry(): bool
    {
        return in_array($this, [self::Pending, self::Failed], true);
    }

    /** Nhân viên xác nhận đã nhận tiền (cổng xác nhận tay, vd. chuyển khoản). */
    public function canConfirmManually(): bool
    {
        return in_array($this, [self::Pending, self::Failed, self::Expired], true);
    }

    /** Chưa thu: hết hạn hoặc huỷ theo đơn được. */
    public function closesWithoutCollection(): bool
    {
        return in_array($this, [self::Pending, self::Failed], true);
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$this->value], true);
    }

    /**
     * @throws InvalidPaymentTransition
     */
    public function transitionTo(self $to): self
    {
        return $this->canTransitionTo($to) ? $to : throw new InvalidPaymentTransition($this, $to);
    }
}
