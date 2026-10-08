<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

/**
 * Service contract (0.3.35): nhóm khách cho giá thành viên. Pricing định nghĩa, module Customer cung cấp (Customer phụ
 * thuộc Pricing, không ngược lại). Không có module/implementation → mọi khách không nhóm (chỉ giá chung).
 */
interface CustomerGroupDirectory
{
    /** Nhóm của khách (một khách tối đa một nhóm); null = không nhóm. */
    public function groupOf(int $customerId): ?int;

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    public function groups(): array;
}
