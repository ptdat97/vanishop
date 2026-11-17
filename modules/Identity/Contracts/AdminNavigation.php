<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

use Closure;

/**
 * Service contract: menu Admin (mục + nhóm), lọc theo quyền nhân viên và plugin đang bật. Contract ở Identity (vòng 0, đứng trước
 * Extension) để mọi module đăng ký menu mà không phụ thuộc tầng Application của Extension; Extension cài đặt
 * (`AdminMenuRegistry`) — architecture-consolidation C1.
 */
interface AdminNavigation
{
    public const FALLBACK_GROUP = 'extensions';

    /**
     * @param  (Closure(): bool)|null  $when  điều kiện hiển thị thêm (vd. chỉ hiện "Báo cáo" khi có báo cáo xem được)
     */
    public function add(string $key, string $label, string $route, ?string $permission = null, int $order = 100, ?string $pluginId = null, ?Closure $when = null, ?string $group = null): void;

    /**
     * Nhóm menu (accordion). `order` xếp nhóm xen kẽ với mục không nhóm cùng thang thứ tự.
     */
    public function group(string $key, string $label, int $order): void;

    /**
     * Các nhóm có ít nhất một mục hiển thị, theo thứ tự.
     *
     * @return list<array{key: string, label: string, order: int}>
     */
    public function visibleGroups(): array;

    /**
     * Màn hình Admin do một plugin thêm vào menu (Admin → Plugin → chi tiết), lọc theo quyền như menu.
     *
     * @return list<array{label: string, url: string}>
     */
    public function forPlugin(string $pluginId): array;

    /**
     * @return list<array{key: string, label: string, url: string, group: string|null}>
     */
    public function visibleItems(): array;
}
