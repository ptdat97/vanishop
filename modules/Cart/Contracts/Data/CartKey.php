<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts\Data;

/**
 * Định danh giỏ công khai + token bí mật do client giữ. Thiếu token đúng thì coi như giỏ không tồn tại.
 */
final readonly class CartKey
{
    public function __construct(
        public string $publicId,
        public string $token,
    ) {}
}
