<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

final class FulfillmentRejected extends BusinessRuleViolation
{
    /**
     * @param  array<string, mixed>  $details
     */
    private function __construct(private readonly string $code_, private readonly int $status_, string $message, private readonly array $details_ = [])
    {
        parent::__construct($message);
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self('shipment.transition_invalid', 409, __('fulfillment::messages.transition_invalid', ['from' => $from, 'to' => $to]), ['from' => $from, 'to' => $to]);
    }

    public static function orderNotReady(string $status): self
    {
        return new self('shipment.order_not_ready', 409, __('fulfillment::messages.order_not_ready', ['status' => $status]), ['status' => $status]);
    }

    public static function alreadyHasShipments(): self
    {
        return new self('shipment.exists', 409, __('fulfillment::messages.exists'));
    }

    public static function allocationMismatch(): self
    {
        return new self('shipment.allocation_mismatch', 422, __('fulfillment::messages.allocation_mismatch'));
    }

    /**
     * Hook `vani.fulfillment.before_push` chặn (EP v3 X2).
     *
     * @param  list<string>  $reasons
     */
    public static function pushBlocked(array $reasons): self
    {
        return new self('fulfillment.push_blocked', 422, __('fulfillment::messages.push_blocked', ['reasons' => implode('; ', $reasons)]));
    }

    public static function returnPickupUnavailable(): self
    {
        return new self('fulfillment.return_pickup_unavailable', 422, __('fulfillment::messages.return_pickup_unavailable'));
    }

    public static function trackingTaken(): self
    {
        return new self('shipment.tracking_taken', 422, __('fulfillment::messages.tracking_taken'));
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
