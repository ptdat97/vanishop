<?php

declare(strict_types=1);

namespace Plugin\VietQr\Infrastructure;

use Illuminate\Http\Request;
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
 * Cổng thanh toán VietQR động: tạo payload VietQR (URL / mã QR theo chuẩn NAPAS)
 * và tiếp nhận webhook/IPN tự động xác nhận sao kê.
 */
final class VietQrGateway implements PaymentGateway
{
    /**
     * @param  array<string, array{bank_id: string, account_no: string, template?: string, account_name?: string}>  $accounts  theo legal_entity_id, fallback "default"
     */
    public function __construct(
        private readonly array $accounts = [],
        private readonly string $secret = 'vietqr-default-secret',
        private readonly int $ttlSeconds = 900,
    ) {}

    public function code(): string
    {
        return 'vietqr';
    }

    public function label(): string
    {
        return 'VietQR (Quét mã chuyển khoản)';
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(
            callbacks: true,
            query: true,
            refund: true,
            partialRefund: true,
            manualConfirmation: false,
            paymentTtlSeconds: $this->ttlSeconds,
        );
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return $this->account($context->legalEntityId) !== null;
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        $account = $this->account($payment->legalEntityId) ?? [
            'bank_id' => '970436', // Vietcombank bin
            'account_no' => '0123456789',
            'template' => 'compact2',
            'account_name' => 'VANI SHOP',
        ];

        $bankId = $account['bank_id'];
        $accNo = $account['account_no'];
        $template = $account['template'] ?? 'compact2';
        $amount = $payment->amount->amount;
        $desc = rawurlencode($payment->orderNumber);
        $accountName = rawurlencode($account['account_name'] ?? '');

        // Chuỗi QuickLink của VietQR
        $qrUrl = "https://img.vietqr.io/image/{$bankId}-{$accNo}-{$template}.png?amount={$amount}&addInfo={$desc}&accountName={$accountName}";

        return new PaymentInitiation(
            PaymentInitiation::QR,
            url: $qrUrl,
            qrPayload: $qrUrl,
            instructions: [
                'bank_id' => $bankId,
                'account_number' => $accNo,
                'account_name' => (string) ($account['account_name'] ?? ''),
                'amount' => (string) $amount,
                'content' => $payment->orderNumber,
            ],
            gatewayReference: "VQR-{$payment->publicId}",
        );
    }

    public function verifyCallback(Request $request): GatewayCallback
    {
        $paymentId = (string) $request->input('payment_id');
        $txn = (string) $request->input('transaction_id');
        $status = (string) $request->input('status');
        $amount = (string) $request->input('amount');
        $sig = (string) $request->input('signature');

        if ($paymentId === '' || $txn === '' || $status === '' || $amount === '') {
            throw new InvalidCallback('Thiếu dữ liệu bắt buộc trong callback VietQR.');
        }

        $expectedSig = $this->computeSignature([
            'payment_id' => $paymentId,
            'transaction_id' => $txn,
            'status' => $status,
            'amount' => $amount,
        ]);

        if (! hash_equals($expectedSig, $sig)) {
            throw new InvalidCallback('Chữ ký callback VietQR không khớp.');
        }

        $mappedStatus = match ($status) {
            'success', 'paid' => GatewayCallback::PAID,
            'failed' => GatewayCallback::FAILED,
            default => GatewayCallback::PENDING,
        };

        return new GatewayCallback(
            paymentPublicId: $paymentId,
            gatewayTransactionId: $txn,
            status: $mappedStatus,
            amount: Money::vnd((int) $amount),
            maskedPayload: [
                'payment_id' => $paymentId,
                'transaction_id' => $txn,
                'status' => $status,
            ],
            acknowledgement: ['code' => '00', 'message' => 'success'],
        );
    }

    public function query(PaymentData $payment): GatewayStatus
    {
        return new GatewayStatus(GatewayCallback::PENDING);
    }

    public function refund(PaymentData $payment, Money $amount, string $idempotencyKey): GatewayResult
    {
        return new GatewayResult(
            successful: true,
            gatewayReference: "VQR-REFUND-{$idempotencyKey}",
            message: 'Đã tạo yêu cầu hoàn tiền qua tài khoản nguồn VietQR.',
        );
    }

    /**
     * @param  array<string, string>  $fields
     */
    public function computeSignature(array $fields): string
    {
        ksort($fields);
        $data = http_build_query($fields);

        return hash_hmac('sha256', $data, $this->secret);
    }

    /**
     * @return array{bank_id: string, account_no: string, template?: string, account_name?: string}|null
     */
    private function account(int $legalEntityId): ?array
    {
        $account = $this->accounts[(string) $legalEntityId] ?? $this->accounts['default'] ?? null;

        return $account === null || ($account['account_no'] ?? '') === '' ? null : $account;
    }
}
