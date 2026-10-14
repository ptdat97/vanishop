<?php

declare(strict_types=1);

namespace Plugin\TrackingPixels\Infrastructure;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Gửi sự kiện mua từ server: Meta Conversions API và/hoặc GA4 Measurement Protocol. Dữ liệu định danh băm SHA-256
 * trước khi gửi. Lỗi/timeout → thử lại có giới hạn; hết lượt thì bỏ (chỉ ghi log khi bật debug). Không ảnh hưởng đơn.
 */
final class SendServerPurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 20;

    /** @var list<int> */
    public array $backoff = [30, 120];

    /**
     * @param  array<string, mixed>  $purchase
     */
    public function __construct(
        public readonly array $purchase,
        public readonly bool $meta,
        public readonly bool $ga4,
    ) {}

    public function handle(TrackingSettings $settings): void
    {
        $failures = [];
        if ($this->meta && ($error = $this->sendMeta($settings)) !== null) {
            $failures[] = $error;
        }
        if ($this->ga4 && ($error = $this->sendGa4($settings)) !== null) {
            $failures[] = $error;
        }
        if ($failures === []) {
            return;
        }
        if ($settings->debug()) {
            Log::warning('vani.tracking-pixels: gửi sự kiện mua từ server lỗi.', ['errors' => $failures, 'transaction_id' => $this->purchase['transaction_id']]);
        }
        if ($this->attempts() < $this->tries) {
            throw new RuntimeException(implode('; ', $failures));
        }
    }

    private function sendMeta(TrackingSettings $settings): ?string
    {
        $customer = $this->purchase['customer'];
        $browser = $this->purchase['browser'];
        $userData = array_filter([
            'client_ip_address' => $browser['ip'],
            'client_user_agent' => $browser['user_agent'],
            'fbp' => $browser['fbp'],
            'fbc' => $browser['fbc'],
            'em' => self::hashed(strtolower(trim((string) $customer['email']))),
            'ph' => self::hashed(preg_replace('/\D+/', '', (string) $customer['phone']) ?? ''),
            'external_id' => self::hashed((string) $customer['id']),
        ], fn ($value): bool => $value !== null && $value !== '' && $value !== []);

        $body = [
            'data' => [[
                'event_name' => 'Purchase',
                'event_time' => $this->purchase['event_time'],
                'event_id' => $this->purchase['event_id'],
                'action_source' => 'website',
                'event_source_url' => $this->purchase['source_url'],
                'user_data' => $userData,
                'custom_data' => [
                    'currency' => $this->purchase['currency'],
                    'value' => $this->purchase['value'],
                    'order_id' => $this->purchase['transaction_id'],
                    'content_type' => 'product',
                    'content_ids' => array_column($this->purchase['items'], 'item_id'),
                    'contents' => array_map(fn (array $item): array => ['id' => $item['item_id'], 'quantity' => $item['quantity'], 'item_price' => $item['price']], $this->purchase['items']),
                ],
            ]],
            'access_token' => $settings->filled('meta_access_token'),
        ];
        if (($test = $settings->filled('meta_test_event_code')) !== null) {
            $body['test_event_code'] = $test;
        }

        return $this->post("https://graph.facebook.com/v19.0/{$settings->metaPixelId()}/events", $body, 'meta');
    }

    private function sendGa4(TrackingSettings $settings): ?string
    {
        $endpoint = $settings->debug() ? 'https://www.google-analytics.com/debug/mp/collect' : 'https://www.google-analytics.com/mp/collect';
        $query = http_build_query(['measurement_id' => $settings->ga4Id(), 'api_secret' => $settings->filled('ga4_api_secret')]);

        return $this->post("{$endpoint}?{$query}", [
            'client_id' => self::gaClientId($this->purchase['browser']['ga'], $this->purchase['event_id']),
            'events' => [[
                'name' => 'purchase',
                'params' => [
                    'transaction_id' => $this->purchase['transaction_id'],
                    'currency' => $this->purchase['currency'],
                    'value' => $this->purchase['value'],
                    'tax' => $this->purchase['tax'],
                    'shipping' => $this->purchase['shipping'],
                    'payment_type' => $this->purchase['payment_type'],
                    'items' => $this->purchase['items'],
                ],
            ]],
        ], 'ga4');
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function post(string $url, array $body, string $platform): ?string
    {
        try {
            $response = Http::asJson()->connectTimeout(3)->timeout(5)->post($url, $body);

            return $response->successful() ? null : "{$platform}: HTTP {$response->status()}";
        } catch (\Throwable $exception) {
            return "{$platform}: {$exception->getMessage()}";
        }
    }

    /**
     * @return list<string>|null
     */
    private static function hashed(string $value): ?array
    {
        return $value === '' ? null : [hash('sha256', $value)];
    }

    /**
     * Mã khách GA4 lấy từ cookie `_ga` (GA1.1.<số>.<thời điểm>); thiếu thì suy ra ổn định từ mã sự kiện.
     */
    private static function gaClientId(?string $cookie, string $eventId): string
    {
        if ($cookie !== null && preg_match('/^GA\d+\.\d+\.(\d+\.\d+)$/', $cookie, $match) === 1) {
            return $match[1];
        }

        return sprintf('%u.%d', crc32($eventId), now()->getTimestamp());
    }
}
