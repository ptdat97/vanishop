<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
final readonly class Recipient
{
    public function __construct(
        /** E.164 */
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $name = null,
        public ?int $customerId = null,
        /** Ngôn ngữ nhận thông báo; null = ngôn ngữ mặc định của cửa hàng (0.3.41, trước là 'vi'). */
        public ?string $locale = null,
    ) {}
}
