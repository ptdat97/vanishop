<?php

declare(strict_types=1);

namespace Plugin\ZaloZns\Infrastructure;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Notification\Contracts\Data\SendResult;

/**
 * Gửi tin ZNS theo template đã duyệt. Access token (~25h) làm mới bằng refresh token; refresh token xoay
 * vòng mỗi lần làm mới nên luôn lưu bản mới nhất. Token hết hạn giữa chừng (lỗi -124) → làm mới rồi gửi lại một lần.
 */
final class ZnsClient
{
    /** Lỗi token (hết hạn/không hợp lệ). */
    private const TOKEN_ERRORS = [-124, -216];

    /** Lỗi tạm thời: vượt hạn mức gọi, hệ thống bận. */
    private const RETRYABLE = [-32, -1, -1000];

    private const ACCESS_KEY = 'vani.zalo-zns.access_token';

    private const REFRESH_KEY = 'vani.zalo-zns.refresh_token';

    public function __construct(
        private readonly Repository $cache,
        private readonly string $apiBase,
        private readonly string $oauthBase,
        private readonly string $appId,
        private readonly string $appSecret,
        private readonly string $initialRefreshToken,
        private readonly string $mode = 'production',
    ) {}

    public function configured(): bool
    {
        return $this->appId !== '' && $this->appSecret !== '' && ($this->initialRefreshToken !== '' || $this->cache->has(self::REFRESH_KEY));
    }

    /**
     * @param  array<string, scalar|null>  $params
     */
    public function send(string $e164, string $templateId, array $params, string $trackingId): SendResult
    {
        if (! $this->configured()) {
            return SendResult::permanent('zns.not_configured');
        }

        $result = $this->attempt($e164, $templateId, $params, $trackingId, forceRefresh: false);
        if ($result === 'token') {
            $result = $this->attempt($e164, $templateId, $params, $trackingId, forceRefresh: true);
        }

        return $result === 'token' ? SendResult::retryable('zns.token_invalid') : $result;
    }

    /**
     * @param  array<string, scalar|null>  $params
     */
    private function attempt(string $e164, string $templateId, array $params, string $trackingId, bool $forceRefresh): SendResult|string
    {
        $token = $this->accessToken($forceRefresh);
        if ($token === null) {
            return SendResult::retryable('zns.token_refresh_failed');
        }

        $body = [
            'phone' => ltrim($e164, '+'),
            'template_id' => $templateId,
            'template_data' => array_map(fn (mixed $value): string => (string) $value, $params),
            'tracking_id' => mb_substr($trackingId, 0, 48),
        ];
        if ($this->mode === 'development') {
            $body['mode'] = 'development';
        }

        try {
            $response = Http::connectTimeout(3)->timeout(10)->acceptJson()->withHeaders(['access_token' => $token])
                ->post(rtrim($this->apiBase, '/').'/message/template', $body);
        } catch (ConnectionException $exception) {
            return SendResult::retryable('zns.connection: '.$exception->getMessage());
        }

        if ($response->serverError() || $response->status() === 429) {
            return SendResult::retryable("zns.http_{$response->status()}");
        }

        $error = (int) $response->json('error', -1);
        if ($error === 0) {
            return SendResult::sent((string) $response->json('data.msg_id', ''));
        }
        if (in_array($error, self::TOKEN_ERRORS, true)) {
            return 'token';
        }

        $message = "zns.{$error}: ".(string) $response->json('message', '');

        return in_array($error, self::RETRYABLE, true) ? SendResult::retryable($message) : SendResult::permanent($message);
    }

    private function accessToken(bool $forceRefresh): ?string
    {
        if (! $forceRefresh) {
            $cached = $this->cache->get(self::ACCESS_KEY);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        return $this->cache->lock('vani.zalo-zns.refresh', 10)->block(5, function () use ($forceRefresh): ?string {
            // Worker khác vừa làm mới trong lúc chờ khoá → dùng luôn.
            $cached = $this->cache->get(self::ACCESS_KEY);
            if (! $forceRefresh && is_string($cached) && $cached !== '') {
                return $cached;
            }

            $refresh = (string) ($this->cache->get(self::REFRESH_KEY) ?? $this->initialRefreshToken);

            try {
                $response = Http::connectTimeout(3)->timeout(10)->asForm()->withHeaders(['secret_key' => $this->appSecret])
                    ->post(rtrim($this->oauthBase, '/').'/v4/oa/access_token', [
                        'refresh_token' => $refresh, 'app_id' => $this->appId, 'grant_type' => 'refresh_token',
                    ]);
            } catch (ConnectionException) {
                return null;
            }

            $access = (string) $response->json('access_token', '');
            if ($access === '') {
                return null;
            }

            $this->cache->forever(self::REFRESH_KEY, (string) $response->json('refresh_token', $refresh));
            $this->cache->put(self::ACCESS_KEY, $access, max(60, (int) $response->json('expires_in', 3600) - 300));

            return $access;
        });
    }
}
