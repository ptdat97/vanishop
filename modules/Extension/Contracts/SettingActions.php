<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

use Modules\Tenancy\Contracts\Data\SettingGroup;

/**
 * Service contract: nút thao tác trên trang một nhóm của Cài đặt hệ thống (vd. "Gửi email thử" ở nhóm Email gửi đi).
 * Nút gửi POST tới route đã đăng ký; route tự kiểm quyền và trả flash (`success`/`error`).
 */
interface SettingActions
{
    /** Nhãn là khoá dịch; route là tên route Admin (POST, không tham số). */
    public function add(SettingGroup $group, string $label, string $route): void;

    /**
     * @return list<array{label: string, url: string}>
     */
    public function for(SettingGroup $group): array;
}
