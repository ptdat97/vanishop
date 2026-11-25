<?php

declare(strict_types=1);

namespace Plugin\AbandonedCart;

use Modules\Cart\Events\CartAbandoned;
use Modules\Extension\PluginServiceProvider;
use Modules\Notification\Contracts\Data\NotificationType;
use Modules\Notification\Contracts\NotificationCatalog;
use Plugin\AbandonedCart\Infrastructure\CartReminder;

/**
 * Nhắc giỏ bỏ quên: Core phát `CartAbandoned` (một lần cho mỗi đợt giỏ không hoạt động, ngưỡng ở Admin → Cài đặt → Bán
 * hàng) → plugin gửi tin `cart_abandoned` nhóm marketing qua `Notifier`. Core chỉ gửi khi khách đã đồng ý nhận ưu đãi qua
 * email và tự gắn link huỷ nhận tin. Admin sửa mẫu ở Admin → Thông báo.
 */
final class AbandonedCartServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.abandoned-cart';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-abandoned-cart');

        $this->app->make(NotificationCatalog::class)->define(new NotificationType(
            CartReminder::TYPE,
            'vani-abandoned-cart::texts.type_label',
            ['customer_name', 'item_count', 'subtotal', 'cart_url', 'unsubscribe_url'],
            ['mail' => ['subject' => 'vani-abandoned-cart::texts.default_subject', 'body' => 'vani-abandoned-cart::texts.default_body']],
        ));

        $this->onEvent(CartAbandoned::class, CartReminder::class);
    }
}
