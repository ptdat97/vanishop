<?php

declare(strict_types=1);

namespace Plugin\AbandonedCart\Infrastructure;

use Modules\Cart\Events\CartAbandoned;
use Modules\Customer\Contracts\Customers;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Notifier;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreLocale;

/**
 * Gửi một email nhắc cho mỗi đợt giỏ bị bỏ quên. Khoá idempotency = mã giỏ + thời điểm hoạt động cuối, nên event phát lại
 * cho cùng đợt không gửi trùng. Khách không có email thì bỏ qua; thiếu đồng ý thì Core ghi nhật ký "bỏ qua".
 */
final class CartReminder
{
    public const TYPE = 'cart_abandoned';

    public function __construct(
        private readonly Customers $customers,
        private readonly Notifier $notifier,
        private readonly MoneyFormatter $money,
    ) {}

    public function handle(CartAbandoned $event): void
    {
        $customer = $this->customers->find($event->customerId);
        if ($customer === null || $customer->email === null) {
            return;
        }
        $locale = $customer->preferredLocale ?? StoreLocale::default();

        $this->notifier->notify(new NotificationRequest(
            type: self::TYPE,
            key: self::TYPE.':'.$event->cartPublicId.':'.$event->lastActivityAt,
            recipient: new Recipient(email: $customer->email, name: $customer->fullName, customerId: $customer->id, locale: $locale),
            variables: [
                'customer_name' => $customer->fullName ?? __('vani-abandoned-cart::texts.customer_fallback', [], $locale),
                'item_count' => $event->itemCount,
                'subtotal' => $this->money->format(Money::of($event->subtotal, $event->currency)),
                'cart_url' => LocalizedUrls::route('storefront.cart', [], $locale),
            ],
            category: NotificationRequest::MARKETING,
        ));
    }
}
