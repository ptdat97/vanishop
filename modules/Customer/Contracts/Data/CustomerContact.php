<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

/**
 * Nơi gửi OTP / tin giao dịch. customerId null = SĐT chưa có hồ sơ.
 */
final readonly class CustomerContact
{
    public function __construct(
        public string $phone,
        public ?string $email = null,
        public ?string $fullName = null,
        public ?int $customerId = null,
    ) {}
}
