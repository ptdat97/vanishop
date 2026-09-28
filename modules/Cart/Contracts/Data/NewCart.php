<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts\Data;

/**
 * Kết quả tạo giỏ: token chỉ trả về MỘT lần — client phải lưu lại.
 */
final readonly class NewCart
{
    public function __construct(
        public CartKey $key,
        public CartView $view,
    ) {}
}
