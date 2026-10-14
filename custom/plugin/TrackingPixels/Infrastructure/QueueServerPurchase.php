<?php

declare(strict_types=1);

namespace Plugin\TrackingPixels\Infrastructure;

use Illuminate\Http\Request;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Events\OrderPlaced;

/**
 * Sau khi đặt hàng (event phát sau commit, trong request của khách): dựng sự kiện mua và đẩy job gửi Meta/GA4 từ server.
 * Chỉ khi bật gửi server-side và khách đã đồng ý cookie (hoặc chế độ không cần đồng ý). Thông tin trình duyệt (IP, user
 * agent, cookie nền tảng) đọc lúc này vì job chạy nền không có request.
 */
final class QueueServerPurchase
{
    public function __construct(
        private readonly TrackingSettings $settings,
        private readonly OrderReader $orders,
        private readonly Request $request,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        $meta = $this->settings->metaServerEnabled();
        $ga4 = $this->settings->ga4ServerEnabled();
        if (! $meta && ! $ga4) {
            return;
        }
        if ($this->settings->consentMode() !== 'none' && $this->request->cookie(TrackingSettings::CONSENT_COOKIE) !== 'granted') {
            return;
        }

        $order = $this->orders->find($event->orderId);
        if ($order === null) {
            return;
        }
        $currency = ['currency' => $order->currencyCode];
        $items = array_map(fn ($line): array => Payloads::item($line->sku, $line->productName, $line->brandName, [...$currency, 'amount' => $line->unitAmount], $line->quantity), $this->orders->lines($order->id));
        $breakdown = $this->orders->priceBreakdown($order->id);

        SendServerPurchase::dispatch([
            'event_id' => Payloads::purchaseEventId($order->publicId),
            'event_time' => now()->getTimestamp(),
            'source_url' => (string) $this->request->headers->get('referer', $this->request->fullUrl()),
            'transaction_id' => $order->number,
            'currency' => $order->currencyCode,
            'value' => Payloads::major([...$currency, 'amount' => $order->totalAmount]),
            'tax' => Payloads::major([...$currency, 'amount' => $breakdown->taxIncluded ?? 0]),
            'shipping' => Payloads::major([...$currency, 'amount' => $breakdown->shipping ?? 0]),
            'payment_type' => $order->paymentMethod,
            'items' => $items,
            'customer' => [
                'email' => $order->recipient['email'] ?? null,
                'phone' => $order->recipient['phone'],
                'id' => $order->customerId === null ? null : (string) $order->customerId,
            ],
            'browser' => [
                'ip' => $this->request->ip(),
                'user_agent' => (string) $this->request->userAgent(),
                'fbp' => $this->request->cookie('_fbp'),
                'fbc' => $this->request->cookie('_fbc'),
                'ga' => $this->request->cookie('_ga'),
            ],
        ], $meta, $ga4);
    }
}
