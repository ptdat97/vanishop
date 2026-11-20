<?php

declare(strict_types=1);

namespace Modules\Payment\Testing;

use Closure;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Modules\Payment\Contracts\CapturesLater;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayPaymentStatus;
use Modules\Payment\Contracts\Data\GatewayResult;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Contracts\Data\InitiationType;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\Data\RefundOutcome;
use Modules\Payment\Contracts\Data\RefundResult;
use Modules\Payment\Contracts\InvalidCallback;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Contracts\QueriesStatus;
use Modules\Payment\Contracts\ReceivesCallbacks;
use Modules\Payment\Contracts\RefundsPayments;
use Modules\Payment\Contracts\TracksRefunds;
use Modules\Shared\Domain\Money\Money;

/**
 * Bộ contract test cho mọi PaymentGateway (Core và plugin). Trong file test Pest của plugin:
 *
 *   PaymentGatewayContract::define('vani.vietqr', fn () => new VietQrGateway(...),
 *       validCallback: fn (PaymentData $p) => Request::create(...),       // callback ký đúng
 *       tamperedCallback: fn (PaymentData $p) => Request::create(...));   // callback bị sửa
 *
 * Kiểm tra: mã hợp lệ; initiate idempotent; callback đúng chữ ký được chấp nhận và khớp payment/số tiền;
 * callback sai bị từ chối (ReceivesCallbacks); query trả trạng thái (QueriesStatus); refund idempotent theo key, cùng kết quả (RefundsPayments/TracksRefunds);
 * cổng giữ tiền (CapturesLater): capture/void idempotent theo key.
 */
final class PaymentGatewayContract
{
    /**
     * @param  Closure(): PaymentGateway  $gateway
     * @param  (Closure(PaymentData): Request)|null  $validCallback
     * @param  (Closure(PaymentData): Request)|null  $tamperedCallback
     */
    public static function define(string $label, Closure $gateway, ?Closure $validCallback = null, ?Closure $tamperedCallback = null): void
    {
        $payment = fn (PaymentGateway $gateway): PaymentData => new PaymentData(
            publicId: '01JCONTRACTTESTPAYMENT0001', gatewayCode: $gateway->code(), orderNumber: 'VN2610-000001',
            amount: Money::vnd(330_000), status: 'pending', gatewayReference: null, expiresAt: new DateTimeImmutable('+15 minutes'),
        );

        describe("PaymentGateway contract: {$label}", function () use ($gateway, $payment, $validCallback, $tamperedCallback): void {
            it('có mã ổn định và nhãn', function () use ($gateway): void {
                $instance = $gateway();
                expect($instance->code())->toMatch('/^[a-z0-9][a-z0-9_.-]*$/')->and($instance->label())->not->toBe('');
            });

            it('initiate trả hành động hợp lệ và idempotent', function () use ($gateway, $payment): void {
                $instance = $gateway();
                $first = $instance->initiate($payment($instance));
                $second = $instance->initiate($payment($instance));

                expect($first->type)->toBeInstanceOf(InitiationType::class)
                    ->and([$second->type, $second->url, $second->qrPayload, $second->gatewayReference])->toBe([$first->type, $first->url, $first->qrPayload, $first->gatewayReference]);
            });

            it('callback: chữ ký đúng được chấp nhận, bị sửa thì từ chối', function () use ($gateway, $payment, $validCallback, $tamperedCallback): void {
                $instance = $gateway();
                $data = $payment($instance);

                if (! $instance instanceof ReceivesCallbacks) {
                    expect($validCallback)->toBeNull('Cổng không nhận callback (không ReceivesCallbacks) thì không cần validCallback.');

                    return;
                }

                expect($validCallback)->not->toBeNull('Cổng có callback phải cung cấp validCallback cho contract test.')
                    ->and($tamperedCallback)->not->toBeNull('Cổng có callback phải cung cấp tamperedCallback cho contract test.');

                $callback = $instance->verifyCallback($validCallback($data));
                expect($callback)->toBeInstanceOf(GatewayCallback::class)
                    ->and($callback->paymentPublicId)->toBe($data->publicId)
                    ->and($callback->gatewayTransactionId)->not->toBe('')
                    ->and($callback->status !== GatewayPaymentStatus::Authorized || $instance instanceof CapturesLater)->toBeTrue('Chỉ cổng CapturesLater được báo Authorized.')
                    ->and($callback->amount->equals($data->amount))->toBeTrue();

                expect(fn () => $instance->verifyCallback($tamperedCallback($data)))->toThrow(InvalidCallback::class);
            });

            it('query trả trạng thái chuẩn hoá (nếu QueriesStatus)', function () use ($gateway, $payment): void {
                $instance = $gateway();
                if (! $instance instanceof QueriesStatus) {
                    expect($instance)->not->toBeInstanceOf(QueriesStatus::class);

                    return;
                }
                $status = $instance->query($payment($instance));

                expect($status)->toBeInstanceOf(GatewayStatus::class)
                    ->and($status->status)->not->toBe(GatewayPaymentStatus::Authorized);
            });

            it('refund idempotent theo key (nếu RefundsPayments)', function () use ($gateway, $payment): void {
                $instance = $gateway();
                if (! $instance instanceof RefundsPayments) {
                    expect($instance->capabilities()->partialRefund)->toBeFalse('partialRefund chỉ có nghĩa khi cổng RefundsPayments.');

                    return;
                }
                $data = $payment($instance);
                $first = $instance->refund($data, Money::vnd(100_000), 'contract-refund-1');
                $second = $instance->refund($data, Money::vnd(100_000), 'contract-refund-1');
                expect($first)->toBeInstanceOf(RefundResult::class)
                    ->and($second->outcome)->toBe($first->outcome)
                    ->and($second->gatewayReference)->toBe($first->gatewayReference);
                if ($first->outcome === RefundOutcome::Pending && $instance instanceof TracksRefunds) {
                    expect($instance->refundStatus($data, 'contract-refund-1', $first->gatewayReference))->toBeInstanceOf(RefundResult::class);
                }
            });

            it('giữ tiền: capture/void idempotent theo key (nếu CapturesLater)', function () use ($gateway, $payment): void {
                $instance = $gateway();
                if (! $instance instanceof CapturesLater) {
                    expect($instance)->not->toBeInstanceOf(CapturesLater::class);

                    return;
                }

                $data = $payment($instance);
                $capture = $instance->capture($data, $data->amount, 'contract-capture-1');
                $void = $instance->void($data, 'contract-void-1');

                expect($capture->successful)->toBeTrue()
                    ->and($instance->capture($data, $data->amount, 'contract-capture-1')->gatewayReference)->toBe($capture->gatewayReference)
                    ->and($void)->toBeInstanceOf(GatewayResult::class)
                    ->and($instance->void($data, 'contract-void-1')->gatewayReference)->toBe($void->gatewayReference);
            });
        });
    }
}
