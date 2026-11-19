<?php

declare(strict_types=1);

namespace Modules\Tenancy\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Nhóm của cấu hình hệ thống (namespace `core`) trên Admin → Cài đặt → Cài đặt hệ thống — mỗi nhóm một trang nhỏ.
 * Thứ tự case = thứ tự hiển thị. Cấu hình `core` không khai nhóm rơi vào `Other`. Cấu hình của plugin nằm ở phần
 * "Cài đặt Plugin" (theo plugin), không dùng nhóm này.
 */
#[PluginApi]
enum SettingGroup: string
{
    case Store = 'store';
    case Appearance = 'appearance';
    case Seo = 'seo';
    case Sales = 'sales';
    case Inventory = 'inventory';
    case Orders = 'orders';
    case Mail = 'mail';
    case Other = 'other';
}
