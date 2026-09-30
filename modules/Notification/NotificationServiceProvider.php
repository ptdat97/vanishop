<?php

declare(strict_types=1);

namespace Modules\Notification;

use Illuminate\Support\Facades\Event;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Notification\Application\Channels\MailChannel;
use Modules\Notification\Application\Listeners\SendOrderNotifications;
use Modules\Notification\Application\NotificationService;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Notification\Contracts\Notifier;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Shared\Support\ModuleServiceProvider;

/**
 * Gửi tin giao dịch/marketing theo template (brand × loại × kênh), nhật ký gửi. Kênh ngoài email là plugin.
 *
 * @see docs/03-domains/notification.md
 */
final class NotificationServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Notification';
    }

    public function register(): void
    {
        $this->app->bind(Notifier::class, NotificationService::class);
        $this->app->make(Extensions::class)->tag([MailChannel::class], NotificationChannel::TAG);
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $permissions->register('notifications.view', 'Xem mẫu tin và nhật ký gửi');
        $permissions->register('notifications.manage', 'Sửa mẫu tin');

        $navigation->add('notifications', 'Thông báo', 'admin.notifications.templates.index', 'notifications.view', 750);

        Event::listen(OrderPlaced::class, [SendOrderNotifications::class, 'orderPlaced']);
        Event::listen(OrderCancelled::class, [SendOrderNotifications::class, 'orderCancelled']);
        Event::listen(ShipmentStatusChanged::class, [SendOrderNotifications::class, 'shipmentStatusChanged']);

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
    }
}
