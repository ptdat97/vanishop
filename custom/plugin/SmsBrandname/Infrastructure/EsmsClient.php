<?php

declare(strict_types=1);

namespace Plugin\SmsBrandname\Infrastructure;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Shared\Domain\Text\VietnameseText;

/**
 * Gửi SMS brandname qua eSMS (SmsType 2 = chăm sóc khách hàng/giao dịch). Nội dung bỏ dấu (tin không dấu,
 * 160 ký tự/tin). `RequestId` = khoá idempotency để eSMS chặn gửi trùng khi retry.
 */
final class EsmsClient
{
    public const OK = '100';

    /** Mã lỗi tạm thời: hết tiền trong tài khoản, hệ thống bận. */
    private const RETRYABLE = ['103', '99'];

    public function __construct(
        private readonly string $apiBase,
        private readonly string $apiKey,
        private readonly string $secretKey,
        private readonly bool $sandbox = false,
    ) {}

    public function configured(): bool
    {
        return $this->apiKey !== '' && $this->secretKey !== '';
    }

    public function send(string $e164, string $content, string $brandname, string $requestId): SendResult
    {
        if (! $this->configured() || $brandname === '') {
            return SendResult::permanent('esms.not_configured');
        }
        // eSMS chỉ gửi trong nước và nhận số dạng 0… (giao thức của nhà cung cấp, không theo luật SĐT của cửa hàng).
        if (preg_match('/^\+84\d{9,10}$/', $e164) !== 1) {
            return SendResult::permanent('esms.unsupported_number');
        }

        try {
            $response = Http::connectTimeout(3)->timeout(10)->acceptJson()->post(rtrim($this->apiBase, '/').'/SendMultipleMessage_V4_post_json/', [
                'ApiKey' => $this->apiKey,
                'SecretKey' => $this->secretKey,
                'Phone' => '0'.substr($e164, 3),
                'Content' => VietnameseText::stripDiacritics($content),
                'Brandname' => $brandname,
                'SmsType' => '2',
                'IsUnicode' => '0',
                'Sandbox' => $this->sandbox ? '1' : '0',
                'RequestId' => mb_substr($requestId, 0, 50),
            ]);
        } catch (ConnectionException $exception) {
            return SendResult::retryable('esms.connection: '.$exception->getMessage());
        }

        if ($response->serverError() || $response->status() === 429) {
            return SendResult::retryable("esms.http_{$response->status()}");
        }

        $code = (string) $response->json('CodeResult', '');
        if ($code === self::OK) {
            return SendResult::sent((string) $response->json('SMSID', ''));
        }

        $error = "esms.{$code}: ".(string) $response->json('ErrorMessage', '');

        return in_array($code, self::RETRYABLE, true) ? SendResult::retryable($error) : SendResult::permanent($error);
    }
}
