<?php

declare(strict_types=1);

namespace Modules\Payment\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Throwable;

/**
 * Không nhận được IPN: hỏi cổng trạng thái các payment treo > 5 phút (cổng hỗ trợ query).
 */
final class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'vani:payment:reconcile';

    protected $description = 'Truy vấn cổng cho các giao dịch treo';

    public function handle(CurrentContext $context, GatewayRegistry $gateways, PaymentService $payments): int
    {
        $context->runAs(ContextScope::system('vani:payment:reconcile'), function () use ($gateways, $payments): void {
            Payment::query()->where('status', PaymentStatus::Pending)->where('created_at', '<', now()->subMinutes(5))
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()->subHour()))
                ->orderBy('id')->chunkById(100, function ($batch) use ($gateways, $payments): void {
                    foreach ($batch as $payment) {
                        $gateway = $gateways->get($payment->gateway_code);
                        if ($gateway === null || ! $gateway->capabilities()->query) {
                            continue;
                        }

                        try {
                            $status = $gateway->query($payments->toData($payment));
                            if (in_array($status->status, [GatewayCallback::PAID, GatewayCallback::FAILED], true) && $status->gatewayTransactionId !== null && $status->amount !== null) {
                                $payments->applyCallback($payment->gateway_code, new GatewayCallback($payment->public_id, $status->gatewayTransactionId, $status->status, $status->amount), 'query');
                            }
                        } catch (Throwable $exception) {
                            Log::warning('Truy vấn giao dịch treo lỗi.', ['payment' => $payment->public_id, 'exception' => $exception]);
                        }
                    }
                });
        });

        return self::SUCCESS;
    }
}
