<?php

declare(strict_types=1);

namespace Plugin\VnPay\Infrastructure;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Payment\Contracts\CallbackResponder;
use Modules\Payment\Contracts\Data\CallbackOutcome;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\GatewayResult;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\InvalidCallback;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Shared\Domain\Money\Money;

/**
 * Cổng VNPay (v2.1.0): chuyển khách sang trang VNPay (ATM nội địa, thẻ quốc tế, QR, ví), IPN về
 * /api/payments/vnpay/callback, tra cứu (querydr) và hoàn tiền (refund) qua API giao dịch.
 *
 * - vnp_TxnRef = public id của payment; thời điểm tạo lấy từ gateway_reference (lần đầu: hạn thanh toán − TTL) → URL
 *   giống nhau mỗi lần (idempotent).
 * - IPN luôn trả HTTP 200 + RspCode theo kết quả Core ghi nhận (CallbackResponder).
 * - Phản hồi API có chữ ký sai/không đọc được → coi như chưa xác định (query: pending; refund: thất bại → hoàn tay).
 */
final class VnPayGateway implements CallbackResponder, PaymentGateway
{
    public const CODE = 'vnpay';

    private const VERSION = '2.1.0';

    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    private const MIN_AMOUNT = 5_000;

    private const MAX_AMOUNT = 999_999_999;

    private const QUERY_RESPONSE_FIELDS = ['vnp_ResponseId', 'vnp_Command', 'vnp_ResponseCode', 'vnp_Message', 'vnp_TmnCode', 'vnp_TxnRef', 'vnp_Amount', 'vnp_BankCode', 'vnp_PayDate', 'vnp_TransactionNo', 'vnp_TransactionType', 'vnp_TransactionStatus', 'vnp_OrderInfo', 'vnp_PromotionCode', 'vnp_PromotionAmount'];

    private const REFUND_RESPONSE_FIELDS = ['vnp_ResponseId', 'vnp_Command', 'vnp_ResponseCode', 'vnp_Message', 'vnp_TmnCode', 'vnp_TxnRef', 'vnp_Amount', 'vnp_BankCode', 'vnp_PayDate', 'vnp_TransactionNo', 'vnp_TransactionType', 'vnp_TransactionStatus', 'vnp_OrderInfo'];

    /** Trường IPN giữ lại để lưu vết (không có dữ liệu thẻ). */
    private const LOGGED_FIELDS = ['vnp_TxnRef', 'vnp_TransactionNo', 'vnp_Amount', 'vnp_ResponseCode', 'vnp_TransactionStatus', 'vnp_BankCode', 'vnp_CardType', 'vnp_PayDate'];

    public function __construct(private readonly VnPaySettings $settings) {}

    public function code(): string
    {
        return self::CODE;
    }

    public function label(): string
    {
        return 'VNPay (thẻ ATM, thẻ quốc tế, QR)';
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(callbacks: true, query: true, refund: true, partialRefund: true, paymentTtlSeconds: $this->settings->ttl());
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return $this->settings->configured() && $context->amount->currency->code === 'VND'
            && $context->amount->amount >= self::MIN_AMOUNT && $context->amount->amount <= self::MAX_AMOUNT;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        $expiresAt = $payment->expiresAt ?? new DateTimeImmutable('+'.$this->settings->ttl().' seconds');
        $params = [
            'vnp_Version' => self::VERSION,
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->settings->tmnCode(),
            'vnp_Amount' => $payment->amount->amount * 100,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $payment->publicId,
            'vnp_OrderInfo' => $this->orderInfo($payment),
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $this->settings->returnUrl(),
            'vnp_IpAddr' => '127.0.0.1',
            'vnp_CreateDate' => $this->createdAt($payment)->format('YmdHis'),
            'vnp_ExpireDate' => $expiresAt->setTimezone(new DateTimeZone(self::TIMEZONE))->format('YmdHis'),
        ];
        $signer = $this->settings->signer();

        return new PaymentInitiation(
            PaymentInitiation::REDIRECT,
            url: $this->settings->payUrl().'?'.$signer->query($params).'&vnp_SecureHash='.$signer->signQuery($params),
            // Lưu thời điểm tạo: querydr/refund cần đúng vnp_TransactionDate kể cả khi TTL đổi sau này.
            gatewayReference: 'VNP-'.$payment->publicId.'-'.$params['vnp_CreateDate'],
        );
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        $params = $this->vnpParams($request);
        foreach (['vnp_TxnRef', 'vnp_Amount', 'vnp_ResponseCode', 'vnp_SecureHash'] as $field) {
            if (($params[$field] ?? '') === '') {
                throw new InvalidCallback("Thiếu trường {$field} trong IPN VNPay.");
            }
        }
        if (! $this->settings->signer()->verifyQuery($params)) {
            throw new InvalidCallback('Chữ ký IPN VNPay không khớp.');
        }
        if (($params['vnp_TmnCode'] ?? $this->settings->tmnCode()) !== $this->settings->tmnCode()) {
            throw new InvalidCallback('IPN VNPay của mã website khác.');
        }

        $paid = $params['vnp_ResponseCode'] === '00' && ($params['vnp_TransactionStatus'] ?? '00') === '00';
        $transactionNo = (string) ($params['vnp_TransactionNo'] ?? '');

        return new GatewayCallback(
            paymentPublicId: (string) $params['vnp_TxnRef'],
            // Giao dịch lỗi có thể không có số giao dịch VNPay (=0): khoá theo mã lỗi + thời điểm để không trùng.
            gatewayTransactionId: $transactionNo !== '' && $transactionNo !== '0'
                ? $transactionNo
                : 'fail:'.$params['vnp_TxnRef'].':'.$params['vnp_ResponseCode'].':'.($params['vnp_PayDate'] ?? ''),
            status: $paid ? GatewayCallback::PAID : GatewayCallback::FAILED,
            amount: Money::vnd(intdiv((int) $params['vnp_Amount'], 100)),
            maskedPayload: array_intersect_key($params, array_flip(self::LOGGED_FIELDS)),
            acknowledgement: ['RspCode' => '00', 'Message' => 'Confirm Success'],
        );
    }

    public function callbackResponse(CallbackOutcome $outcome, ?GatewayCallback $callback): array
    {
        [$code, $message] = match ($outcome) {
            CallbackOutcome::Applied => ['00', 'Confirm Success'],
            CallbackOutcome::Duplicate => ['02', 'Order already confirmed'],
            CallbackOutcome::AmountMismatch => ['04', 'Invalid amount'],
            CallbackOutcome::NotFound => ['01', 'Order not found'],
            CallbackOutcome::Invalid => ['97', 'Invalid signature'],
        };

        return ['status' => 200, 'body' => ['RspCode' => $code, 'Message' => $message]];
    }

    /**
     * Kết quả hiển thị cho khách ở Return URL (KHÔNG ghi nhận thanh toán — chỉ IPN mới ghi nhận).
     *
     * @param  array<string, mixed>  $params
     * @return 'paid'|'failed'|'invalid'
     */
    public function returnResult(array $params): string
    {
        if (! $this->settings->signer()->verifyQuery($params)) {
            return 'invalid';
        }

        return ($params['vnp_ResponseCode'] ?? '') === '00' && ($params['vnp_TransactionStatus'] ?? '00') === '00' ? 'paid' : 'failed';
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        $response = $this->queryTransaction($payment);
        if ($response === null || ($response['vnp_ResponseCode'] ?? '') !== '00') {
            return new GatewayStatus(GatewayCallback::PENDING);
        }

        $amount = Money::vnd(intdiv((int) ($response['vnp_Amount'] ?? 0), 100));

        return match ((string) ($response['vnp_TransactionStatus'] ?? '')) {
            '00' => new GatewayStatus(GatewayCallback::PAID, (string) ($response['vnp_TransactionNo'] ?? ''), $amount),
            '02' => new GatewayStatus(GatewayCallback::FAILED, (string) ($response['vnp_TransactionNo'] ?? ''), $amount),
            default => new GatewayStatus(GatewayCallback::PENDING),
        };
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        $done = DB::table('plg_vnpay_refunds')->where('idempotency_key', $idempotencyKey)->value('gateway_reference');
        if ($done !== null) {
            return new GatewayResult(true, (string) $done, 'Đã hoàn trước đó.');
        }

        // Hoàn tiền cần số giao dịch VNPay + thời điểm thanh toán gốc → tra cứu trước.
        $original = $this->queryTransaction($payment);
        if ($original === null || ($original['vnp_ResponseCode'] ?? '') !== '00' || ($original['vnp_TransactionStatus'] ?? '') !== '00') {
            return new GatewayResult(false, message: 'VNPay chưa xác nhận giao dịch thanh toán gốc — hoàn tiền thủ công.');
        }

        $requestId = substr(hash('sha256', 'refund|'.$idempotencyKey), 0, 32); // cố định theo key: gửi lại không hoàn hai lần
        $now = $this->now();
        $body = [
            'vnp_RequestId' => $requestId,
            'vnp_Version' => self::VERSION,
            'vnp_Command' => 'refund',
            'vnp_TmnCode' => $this->settings->tmnCode(),
            'vnp_TransactionType' => $amount->amount === $payment->amount->amount ? '02' : '03',
            'vnp_TxnRef' => $payment->publicId,
            'vnp_Amount' => $amount->amount * 100,
            'vnp_TransactionNo' => (string) ($original['vnp_TransactionNo'] ?? ''),
            'vnp_TransactionDate' => $this->createdAt($payment)->format('YmdHis'),
            'vnp_CreateBy' => 'vanishop',
            'vnp_CreateDate' => $now,
            'vnp_IpAddr' => '127.0.0.1',
            'vnp_OrderInfo' => 'Hoan tien '.$this->asciiOrderNumber($payment),
        ];
        $body['vnp_SecureHash'] = $this->settings->signer()->signPipe([
            $body['vnp_RequestId'], $body['vnp_Version'], $body['vnp_Command'], $body['vnp_TmnCode'], $body['vnp_TransactionType'],
            $body['vnp_TxnRef'], $body['vnp_Amount'], $body['vnp_TransactionNo'], $body['vnp_TransactionDate'], $body['vnp_CreateBy'],
            $body['vnp_CreateDate'], $body['vnp_IpAddr'], $body['vnp_OrderInfo'],
        ]);

        $response = $this->post($body, self::REFUND_RESPONSE_FIELDS);
        if ($response === null || ($response['vnp_ResponseCode'] ?? '') !== '00') {
            return new GatewayResult(false, message: 'VNPay từ chối hoàn tiền'.($response === null ? '' : ' (mã '.($response['vnp_ResponseCode'] ?? '?').')').' — hoàn tiền thủ công.');
        }

        $reference = 'VNP-RF-'.((string) ($response['vnp_TransactionNo'] ?? '') ?: $requestId);
        DB::table('plg_vnpay_refunds')->insertOrIgnore([
            'idempotency_key' => $idempotencyKey, 'payment_public_id' => $payment->publicId, 'amount' => $amount->amount,
            'gateway_reference' => $reference, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return new GatewayResult(true, $reference);
    }

    /**
     * @return array<string, mixed>|null phản hồi querydr đã xác minh chữ ký
     */
    private function queryTransaction(PaymentData $payment): ?array
    {
        if (! $this->settings->configured()) {
            return null;
        }

        $body = [
            'vnp_RequestId' => Str::lower(Str::random(32)),
            'vnp_Version' => self::VERSION,
            'vnp_Command' => 'querydr',
            'vnp_TmnCode' => $this->settings->tmnCode(),
            'vnp_TxnRef' => $payment->publicId,
            'vnp_OrderInfo' => 'Tra cuu '.$this->asciiOrderNumber($payment),
            'vnp_TransactionDate' => $this->createdAt($payment)->format('YmdHis'),
            'vnp_CreateDate' => $this->now(),
            'vnp_IpAddr' => '127.0.0.1',
        ];
        $body['vnp_SecureHash'] = $this->settings->signer()->signPipe([
            $body['vnp_RequestId'], $body['vnp_Version'], $body['vnp_Command'], $body['vnp_TmnCode'], $body['vnp_TxnRef'],
            $body['vnp_TransactionDate'], $body['vnp_CreateDate'], $body['vnp_IpAddr'], $body['vnp_OrderInfo'],
        ]);

        return $this->post($body, self::QUERY_RESPONSE_FIELDS);
    }

    /**
     * @param  array<string, scalar>  $body
     * @param  list<string>  $responseFields
     * @return array<string, mixed>|null
     */
    private function post(array $body, array $responseFields): ?array
    {
        try {
            $response = Http::timeout(15)->acceptJson()->asJson()->post($this->settings->apiUrl(), $body);
        } catch (ConnectionException $exception) {
            Log::warning('Không gọi được API VNPay.', ['command' => $body['vnp_Command'], 'error' => $exception->getMessage()]);

            return null;
        }

        $data = $response->json();
        if (! $response->successful() || ! is_array($data)) {
            Log::warning('API VNPay trả lỗi.', ['command' => $body['vnp_Command'], 'status' => $response->status()]);

            return null;
        }
        if (! $this->settings->signer()->verifyPipe($data, $responseFields)) {
            Log::warning('Chữ ký phản hồi API VNPay không khớp — bỏ qua.', ['command' => $body['vnp_Command'], 'response_code' => $data['vnp_ResponseCode'] ?? null]);

            return null;
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function vnpParams(Request $request): array
    {
        $params = [];
        foreach ($request->query() as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'vnp_') && is_scalar($value)) {
                $params[$key] = (string) $value;
            }
        }

        return $params;
    }

    private function createdAt(PaymentData $payment): DateTimeImmutable
    {
        if ($payment->gatewayReference !== null && preg_match('/-(\d{14})$/', $payment->gatewayReference, $match) === 1) {
            $stored = DateTimeImmutable::createFromFormat('YmdHis', $match[1], new DateTimeZone(self::TIMEZONE));
            if ($stored !== false) {
                return $stored;
            }
        }

        $expiresAt = $payment->expiresAt ?? new DateTimeImmutable;

        return $expiresAt->modify('-'.$this->settings->ttl().' seconds')->setTimezone(new DateTimeZone(self::TIMEZONE));
    }

    private function now(): string
    {
        return now()->timezone(self::TIMEZONE)->format('YmdHis');
    }

    private function orderInfo(PaymentData $payment): string
    {
        return 'Thanh toan don hang '.$this->asciiOrderNumber($payment);
    }

    /** VNPay chỉ nhận chữ không dấu, không ký tự đặc biệt trong OrderInfo. */
    private function asciiOrderNumber(PaymentData $payment): string
    {
        return (string) preg_replace('/[^A-Za-z0-9 ]/', '', Str::ascii($payment->orderNumber));
    }
}
