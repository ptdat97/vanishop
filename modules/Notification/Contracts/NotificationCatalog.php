<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts;

use Modules\Notification\Contracts\Data\NotificationType;
use Modules\Shared\Contracts\PluginApi;

/**
 * Danh mục loại tin. Core và plugin khai báo trong boot(): plugin gửi loại tin riêng qua `Notifier`, kèm mẫu
 * mặc định để tin gửi được ngay khi Admin chưa tạo mẫu.
 */
#[PluginApi]
interface NotificationCatalog
{
    public function define(NotificationType $type): void;

    /**
     * @return array<string, NotificationType> code => type
     */
    public function types(): array;
}
