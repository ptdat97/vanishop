<?php

declare(strict_types=1);

namespace Modules\Notification;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Event;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Modules\Extension\Contracts\SettingActions;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Notification\Application\Channels\MailChannel;
use Modules\Notification\Application\InMemoryNotificationCatalog;
use Modules\Notification\Application\Listeners\SendOrderNotifications;
use Modules\Notification\Application\Mail\MailSettings;
use Modules\Notification\Application\Mail\SettingsMailTransport;
use Modules\Notification\Application\NotificationService;
use Modules\Notification\Console\PruneLogsCommand;
use Modules\Notification\Contracts\Data\NotificationType;
use Modules\Notification\Contracts\NotificationCatalog;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Notification\Contracts\Notifier;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

/**
 * Gửi tin giao dịch/marketing theo template (loại × kênh), nhật ký gửi. Kênh ngoài email là plugin.
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
        $this->app->singleton(NotificationCatalog::class, InMemoryNotificationCatalog::class);
        $this->app->make(Extensions::class)->tag([MailChannel::class], NotificationChannel::TAG);
        $this->app->make(Extensions::class)->requires(NotificationChannel::TAG, Requirement::AtLeastOne, 'notification::texts.notification_service_provider.notification_channels');
        $this->app->make(Extensions::class)->kindContract('notification_channel', NotificationChannel::TAG);
        // Mailer `vani` (mặc định, config/mail.php): theo Admin → Cài đặt → Email gửi đi, đọc lúc gửi.
        $this->app->afterResolving(MailManager::class, function (MailManager $mail): void {
            $mail->extend('vani', fn (array $config): SettingsMailTransport => new SettingsMailTransport($this->app, (string) ($config['fallback'] ?? 'log')));
        });
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        // Lưu giữ nhật ký gửi tin (db-refactor G3).
        $this->app->make(Settings::class)->define(new SettingDefinition(
            'core', PruneLogsCommand::SETTING, 'notification::texts.notification_service_provider.retention_months', 'int', 6,
            help: 'notification::texts.notification_service_provider.retention_months_help', rules: ['min:1', 'max:120'], group: SettingGroup::Other,
        ));
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:notification:prune')->dailyAt('04:20')->withoutOverlapping()->onOneServer();
        });
        if ($this->app->runningInConsole()) {
            $this->commands([PruneLogsCommand::class]);
        }

        $catalog = $this->app->make(NotificationCatalog::class);
        $order = ['store_name', 'customer_name', 'order_number', 'total'];
        $catalog->define(new NotificationType('order_placed', 'notification::texts.notification_service_provider.order_placed', $order));
        $catalog->define(new NotificationType('order_cancelled', 'notification::texts.notification_service_provider.order_cancelled', $order));
        $catalog->define(new NotificationType('shipment_shipped', 'notification::texts.notification_service_provider.order_out_delivery', [...$order, 'carrier', 'tracking_number']));
        $catalog->define(new NotificationType('shipment_delivered', 'notification::texts.notification_service_provider.order_delivered', [...$order, 'carrier', 'tracking_number']));

        $permissions->register('notifications.view', 'notification::texts.notification_service_provider.view_message_templates_send_log', [PermissionRegistry::PRESET_ORDER_OPS, PermissionRegistry::PRESET_MARKETING]);
        $permissions->register('notifications.manage', 'notification::texts.notification_service_provider.edit_message_templates', [PermissionRegistry::PRESET_MARKETING]);

        $navigation->add('notifications', 'notification::texts.notification_service_provider.notifications', 'admin.notifications.templates.index', 'notifications.view', 220, group: 'marketing');

        Event::listen(OrderPlaced::class, [SendOrderNotifications::class, 'orderPlaced']);
        Event::listen(OrderCancelled::class, [SendOrderNotifications::class, 'orderCancelled']);
        Event::listen(ShipmentStatusChanged::class, [SendOrderNotifications::class, 'shipmentStatusChanged']);

        MailSettings::define($this->app->make(Settings::class));
        $this->app->make(SettingActions::class)->add(SettingGroup::Mail, 'notification::texts.mail_settings.send_test', 'admin.notifications.mail.test');

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
    }
}
