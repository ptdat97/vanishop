<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts;

/**
 * Extension point (tag `vani.cart.line_options`, ADR-030 W4): plugin nhận tuỳ chọn trên dòng giỏ (lời chúc gói quà,
 * chữ khắc…). Khách gửi `options.<plugin-id>.<field>` khi thêm dòng; Core gọi implementation của đúng plugin đó,
 * lưu giá trị đã chuẩn hoá trên dòng giỏ và chụp sang dòng đơn (bất biến).
 *
 * Cùng variant khác tuỳ chọn là hai dòng. Tuỳ chọn **không đổi giá** (phụ thu: Designed — cần dòng phí trong totals).
 * Không I/O mạng (chạy khi khoá giỏ).
 */
interface CartLineOption
{
    public const TAG = 'vani.cart.line_options';

    /**
     * Kiểm tra + chuẩn hoá tuỳ chọn của plugin cho variant. Trả mảng rỗng = coi như không có tuỳ chọn.
     *
     * @param  array<string, mixed>  $values  dữ liệu khách gửi dưới `options.<plugin-id>`
     * @return array<string, scalar|null> giá trị lưu trên dòng (JSON được)
     *
     * @throws InvalidCartLineOption
     */
    public function normalize(int $variantId, array $values): array;
}
