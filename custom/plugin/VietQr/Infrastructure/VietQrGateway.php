<?php

declare(strict_types=1);

namespace Plugin\VietQr\Infrastructure;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayCapabilities;
use Modules\Payment\Contracts\Data\GatewayPaymentStatus;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\InvalidCallback;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Contracts\ReceivesCallbacks;
use Modules\Shared\Domain\Money\Money;
use RuntimeException;

/**
 * Cổng thanh toán VietQR động: tạo payload VietQR (URL / mã QR theo chuẩn NAPAS)
 * và tiếp nhận webhook/IPN tự động xác nhận sao kê.
 *
 * Chỉ khả dụng khi có **cả** tài khoản nhận tiền và khoá ký callback (`VIETQR_SECRET`): khoá rỗng thì ai cũng ký được
 * callback "đã trả". Không tra cứu và không hoàn tiền qua cổng (chuyển khoản ngân hàng) — khoản hoàn chờ nhân viên/kênh
 * chuyển trả (`RefundPayout`).
 */
final class VietQrGateway implements PaymentGateway, ReceivesCallbacks
{
    /**
     * @param  array{bank_id?: string, account_no?: string, template?: string, account_name?: string}  $account  tài khoản nhận tiền của cửa hàng
     */
    public function __construct(
        private readonly array $account = [],
        private readonly string $secret = '',
        private readonly int $ttlSeconds = 900,
    ) {}

    public function code(): string
    {
        return 'vietqr';
    }

    public function label(): string
    {
        return __('vani-vietqr::texts.viet_qr_gateway.vietqr_scan_transfer');
    }

    public function capabilities(): GatewayCapabilities
    {
        return new GatewayCapabilities(paymentTtlSeconds: $this->ttlSeconds);
    }

    public function isAvailable(PaymentContext $context): bool
    {
        return $this->account() !== null && $this->secret !== '';
    }

    public function initiate(PaymentData $payment): PaymentInitiation
    {
        $account = $this->account() ?? throw new RuntimeException('VietQR chưa cấu hình tài khoản nhận tiền.'); // i18n-ignore: lỗi kỹ thuật, ghi log
        $bankId = $account['bank_id'] ?? '';
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

        if ($this->secret === '' || $paymentId === '' || $txn === '' || $status === '' || $amount === '' || $sig === '') {
            throw new InvalidCallback(__('vani-vietqr::texts.viet_qr_gateway.required_data_missing_from_vietqr'));
        }

        $expectedSig = $this->computeSignature([
            'payment_id' => $paymentId,
            'transaction_id' => $txn,
            'status' => $status,
            'amount' => $amount,
        ]);

        if (! hash_equals($expectedSig, $sig)) {
            throw new InvalidCallback(__('vani-vietqr::texts.viet_qr_gateway.vietqr_callback_signature_does_not'));
        }

        $mappedStatus = match ($status) {
            'success', 'paid' => GatewayPaymentStatus::Paid,
            'failed' => GatewayPaymentStatus::Failed,
            default => GatewayPaymentStatus::Pending,
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
     * @return array{bank_id?: string, account_no: string, template?: string, account_name?: string}|null
     */
    private function account(): ?array
    {
        return ($this->account['account_no'] ?? '') === '' ? null : $this->account;
    }
}
