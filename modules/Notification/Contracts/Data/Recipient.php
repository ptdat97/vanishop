<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts\Data;

final readonly class Recipient
{
    public function __construct(
        /** E.164 */
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $name = null,
        public ?int $customerId = null,
        public string $locale = 'vi',
    ) {}
}
