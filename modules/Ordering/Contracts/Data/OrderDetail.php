<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

/**
 * Đơn đầy đủ để hiển thị (khách hoặc Admin), từ snapshot — không đọc catalog hiện tại.
 */
final readonly class OrderDetail
{
    /**
     * @param  array{code: string, label: string}  $customerStatus
     * @param  array{full_name: string, phone: string, email: ?string}  $customer
     * @param  array<string, string>  $shippingAddress
     * @param  array<string, mixed>  $shippingMethod
     * @param  list<array<string, mixed>>  $lines
     * @param  list<array<string, mixed>>  $adjustments
     * @param  list<array<string, mixed>>  $events
     * @param  array<string, int>  $amounts  subtotal, discount, shipping, tax, total
     */
    public function __construct(
        public int $id,
        public string $publicId,
        public string $number,
        public string $orderStatus,
        public string $paymentStatus,
        public string $fulfillmentStatus,
        public string $returnStatus,
        public string $paymentMethod,
        public array $customerStatus,
        public string $currencyCode,
        public array $amounts,
        public array $customer,
        public array $shippingAddress,
        public array $shippingMethod,
        public ?string $note,
        public string $placedAt,
        public array $lines,
        public array $adjustments,
        public array $events,
        public bool $cancellableByCustomer,
        public int $lockVersion,
        public string $source = 'web',
    ) {}
}
