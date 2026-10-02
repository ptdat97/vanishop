<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

/**
 * Danh tính do nhà cung cấp đăng nhập trả về. Chỉ đặt `phoneVerified`/`emailVerified` = true khi nhà cung cấp
 * **cam kết** đã xác minh — Core dựa vào cờ này để ghép với tài khoản có sẵn.
 */
final readonly class ExternalIdentity
{
    public function __construct(
        public string $provider,
        /** Id ổn định của người dùng phía nhà cung cấp. */
        public string $subject,
        public ?string $phone = null,
        public bool $phoneVerified = false,
        public ?string $email = null,
        public bool $emailVerified = false,
        public ?string $fullName = null,
    ) {}
}
