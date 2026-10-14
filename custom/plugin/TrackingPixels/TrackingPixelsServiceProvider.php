<?php

declare(strict_types=1);

namespace Plugin\TrackingPixels;

use Modules\Extension\PluginServiceProvider;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Shared\Domain\Money\Currency;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Contracts\Data\SlotView;
use Plugin\TrackingPixels\Infrastructure\Payloads;
use Plugin\TrackingPixels\Infrastructure\QueueServerPurchase;
use Plugin\TrackingPixels\Infrastructure\TrackingSettings;

/**
 * Theo dõi quảng cáo: Meta Pixel, GA4, GTM ở trình duyệt (sau khi khách đồng ý cookie) + sự kiện mua từ server
 * (Meta Conversions API, GA4 Measurement Protocol). Đặc tả: docs/05-plugin/specs/tracking-pixels.md.
 */
final class TrackingPixelsServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.tracking-pixels';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->settings($this->definitions());
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-tracking-pixels');

        $this->onSlot('vani.storefront.layout.head', fn (): ?SlotView => $this->tracking()->anyConfigured()
            ? new SlotView('vani-tracking-pixels::head', ['config' => $this->clientConfig()])
            : null);
        $this->onSlot('vani.storefront.layout.body_end', fn (): ?SlotView => $this->tracking()->anyConfigured() && $this->tracking()->consentMode() === 'banner'
            ? new SlotView('vani-tracking-pixels::consent-banner', ['text' => $this->localized('consent_text', 'Chúng tôi dùng cookie để đo hiệu quả quảng cáo và cải thiện trải nghiệm mua sắm.')])
            : null);

        $this->onSlot('vani.storefront.pdp.after_details', fn (array $product): ?SlotView => $this->events([['view_item', Payloads::viewItem($product)]]));
        $this->onSlot('vani.storefront.plp.before_listing', function (array $context, array $listing): ?SlotView {
            $events = [['view_item_list', Payloads::viewItemList($context, $listing)]];
            if ($context['type'] === 'search' && ($term = trim((string) request()->query('q', ''))) !== '') {
                array_unshift($events, ['search', ['search_term' => $term]]);
            }

            return $this->events($events);
        });
        $this->onSlot('vani.storefront.checkout.before_submit', fn (array $quote): ?SlotView => $quote['lines'] === [] ? null : $this->events([['begin_checkout', Payloads::beginCheckout($quote)]]));
        $this->onSlot('vani.storefront.order.after_summary', fn (array $order): ?SlotView => $this->events([['purchase', Payloads::purchase($order)]]));

        $this->onEvent(OrderPlaced::class, QueueServerPurchase::class);
    }

    private function tracking(): TrackingSettings
    {
        return $this->app->make(TrackingSettings::class);
    }

    /**
     * @param  list<array{0: string, 1: array<string, mixed>}>  $events
     */
    private function events(array $events): ?SlotView
    {
        return $this->tracking()->anyConfigured() ? new SlotView('vani-tracking-pixels::events', ['events' => $events]) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function clientConfig(): array
    {
        $tracking = $this->tracking();

        return [
            'meta' => $tracking->metaPixelId(),
            'ga4' => $tracking->ga4Id(),
            'gtm' => $tracking->gtmId(),
            'consent' => $tracking->consentMode(),
            'consent_expression' => $tracking->consentMode() === 'custom' ? $tracking->filled('consent_expression') : null,
            'cookie' => TrackingSettings::CONSENT_COOKIE,
            // Dữ liệu giỏ từ theme (`vani:cart-updated`) có tiền ở đơn vị nhỏ nhất; nền tảng nhận đơn vị chính.
            'currency_exponent' => Currency::of((string) config('vanishop.currency'))->exponent,
            'debug' => $tracking->debug(),
        ];
    }

    private function localized(string $key, string $default): string
    {
        return $this->tracking()->filled($key.'.'.app()->getLocale()) ?? $this->tracking()->filled($key.'.'.StoreLocale::default()) ?? $default;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        $definitions = [
            ['key' => 'meta_pixel_id', 'label' => 'Meta Pixel ID', 'help' => 'Ví dụ 123456789012345. Trống = tắt Meta.', 'rules' => ['regex:/^\d{5,20}$/']],
            ['key' => 'ga4_measurement_id', 'label' => 'GA4 Measurement ID', 'help' => 'Ví dụ G-XXXXXXXXXX. Trống = tắt GA4.', 'rules' => ['regex:/^G-[A-Z0-9]{4,20}$/']],
            ['key' => 'gtm_container_id', 'label' => 'Google Tag Manager ID', 'help' => 'Ví dụ GTM-XXXXXXX. Trống = tắt GTM.', 'rules' => ['regex:/^GTM-[A-Z0-9]{4,12}$/']],
            ['key' => 'consent_mode', 'label' => 'Đồng ý cookie', 'type' => 'select', 'default' => 'banner',
                'options' => ['banner' => 'Hiện thanh đồng ý của plugin', 'custom' => 'Biểu thức JS của công cụ đồng ý khác', 'none' => 'Không hỏi (luôn chạy)'],
                'help' => 'Chưa đồng ý thì không nạp script quảng cáo và không gửi sự kiện từ server.'],
            ['key' => 'consent_expression', 'label' => 'Biểu thức JS đồng ý', 'type' => 'text', 'help' => 'Khi chọn "Biểu thức JS". Ví dụ: window.CookieConsent && window.CookieConsent.acceptedCategory("analytics")'],
            ['key' => 'meta_capi_enabled', 'label' => 'Gửi sự kiện mua từ server tới Meta (Conversions API)', 'type' => 'bool'],
            ['key' => 'meta_access_token', 'label' => 'Meta Conversions API access token', 'type' => 'secret'],
            ['key' => 'meta_test_event_code', 'label' => 'Mã Test Event của Meta', 'help' => 'Chỉ dùng khi thử trong Events Manager.'],
            ['key' => 'ga4_mp_enabled', 'label' => 'Gửi sự kiện mua từ server tới GA4 (Measurement Protocol)', 'type' => 'bool'],
            ['key' => 'ga4_api_secret', 'label' => 'GA4 API secret', 'type' => 'secret'],
            ['key' => 'debug', 'label' => 'Chế độ debug', 'type' => 'bool', 'help' => 'Ghi log lỗi gửi, dùng endpoint debug của GA4, in sự kiện ra console trình duyệt.'],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $definitions[] = ['key' => "consent_text.{$locale}", 'label' => "Nội dung thanh đồng ý ({$locale})", 'type' => 'text'];
        }

        return $definitions;
    }
}
