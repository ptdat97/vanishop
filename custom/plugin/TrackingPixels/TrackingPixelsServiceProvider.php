<?php

declare(strict_types=1);

namespace Plugin\TrackingPixels;

use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
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
        // Chuỗi giao diện storefront của plugin theo ngôn ngữ (khoá = câu tiếng Việt, ADR-038); theme ghi đè được.
        $this->translations($this->pluginPath('Resources/lang'), 'vani-tracking-pixels');
        $this->settings($this->definitions(), [PermissionRegistry::PRESET_MARKETING]);
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-tracking-pixels');

        $this->onSlot('vani.storefront.layout.head', fn (): ?SlotView => $this->tracking()->anyConfigured()
            ? new SlotView('vani-tracking-pixels::head', ['config' => $this->clientConfig()])
            : null);
        $this->onSlot('vani.storefront.layout.body_end', fn (): ?SlotView => $this->tracking()->anyConfigured() && $this->tracking()->consentMode() === 'banner'
            ? new SlotView('vani-tracking-pixels::consent-banner', ['text' => $this->localized('consent_text', __('vani-tracking-pixels::texts.tracking_pixels_service_provider.we_use_cookies_measure_advertising'))])
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
            ['key' => 'meta_pixel_id', 'label' => 'Meta Pixel ID', 'help' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.example_123456789012345_empty_meta_off', 'rules' => ['regex:/^\d{5,20}$/']],
            ['key' => 'ga4_measurement_id', 'label' => 'GA4 Measurement ID', 'help' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.example_xxxxxxxxxx_empty_ga4_off', 'rules' => ['regex:/^G-[A-Z0-9]{4,20}$/']],
            ['key' => 'gtm_container_id', 'label' => 'Google Tag Manager ID', 'help' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.example_gtm_xxxxxxx_empty_gtm', 'rules' => ['regex:/^GTM-[A-Z0-9]{4,12}$/']],
            ['key' => 'consent_mode', 'label' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.cookie_consent', 'type' => 'select', 'default' => 'banner',
                'options' => ['banner' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.show_plugin_s_consent_bar', 'custom' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.js_expression_another_consent_tool', 'none' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.do_not_ask_always_run'],
                'help' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.without_consent_ad_scripts_not'],
            ['key' => 'consent_expression', 'label' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.consent_js_expression', 'type' => 'text', 'help' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.when_js_expression_chosen_example'],
            ['key' => 'meta_capi_enabled', 'label' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.send_purchase_events_from_server', 'type' => 'bool'],
            ['key' => 'meta_access_token', 'label' => 'Meta Conversions API access token', 'type' => 'secret'],
            ['key' => 'meta_test_event_code', 'label' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.meta_test_event_code', 'help' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.only_when_testing_events_manager'],
            ['key' => 'ga4_mp_enabled', 'label' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.send_purchase_events_from_server_2', 'type' => 'bool'],
            ['key' => 'ga4_api_secret', 'label' => 'GA4 API secret', 'type' => 'secret'],
            ['key' => 'debug', 'label' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.debug_mode', 'type' => 'bool', 'help' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.logs_send_errors_uses_ga4'],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $definitions[] = ['key' => "consent_text.{$locale}", 'label' => 'vani-tracking-pixels::texts.tracking_pixels_service_provider.consent_bar_text_locale', 'type' => 'text'];
        }

        return $definitions;
    }
}
