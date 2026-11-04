<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

/**
 * Extension point: một loại link có kiểu (danh mục, thương hiệu, trang nội dung của plugin…) cho menu và khối giao
 * diện. Core có `category`, `collection`, `brand`, `product`, `route`, `url`; plugin thêm loại (vd. `vani.cms`: `page`).
 *
 * Phân giải theo lô: `resolve()` nhận mọi giá trị của loại này trong một menu — một truy vấn cho cả lô. Không cache
 * qua request (đổi slug phải thấy ngay); `LinkResolver` ghi nhớ trong request.
 */
interface LinkType
{
    public const TAG = 'vani.storefront.link_types';

    /** Mã loại ổn định, dạng `[a-z][a-z0-9_]*`. */
    public function type(): string;

    /** Tên hiển thị trong Admin. */
    public function label(): string;

    /** Admin chọn giá trị bằng tìm kiếm (`search()`); false = nhập tay (URL) hoặc chọn từ `options()`. */
    public function searchable(): bool;

    /**
     * Giá trị cố định để chọn (vd. route storefront công khai); rỗng nếu loại dùng tìm kiếm hoặc nhập tay.
     *
     * @return list<array{value: string, label: string}>
     */
    public function options(): array;

    /**
     * @return list<array{value: string, label: string}>
     */
    public function search(string $query, string $locale, int $limit = 20): array;

    /** Giá trị hợp lệ về dạng (vd. URL không phải `javascript:`), chưa cần đối tượng tồn tại. */
    public function accepts(string $value): bool;

    /**
     * Phân giải theo lô; giá trị không có trong kết quả = đối tượng không tồn tại/không hiển thị.
     *
     * @param  list<string>  $values
     * @return array<string, array{url: string, label: string}>
     */
    public function resolve(array $values, string $locale): array;
}
