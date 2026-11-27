<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Thông tin ngắn của khách cho màn hình Admin của plugin (chọn khách, danh sách mã đã phát).
 */
#[PluginApi]
final readonly class CustomerSummary
{
    public function __construct(
        public int $id,
        public ?string $fullName,
        public ?string $phone,
        public ?string $email,
    ) {}
}
