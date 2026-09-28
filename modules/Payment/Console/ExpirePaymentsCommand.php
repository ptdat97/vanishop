<?php

declare(strict_types=1);

namespace Modules\Payment\Console;

use Illuminate\Console\Command;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class ExpirePaymentsCommand extends Command
{
    protected $signature = 'vani:payment:expire';

    protected $description = 'Huỷ đơn chưa thanh toán quá hạn (nhả hàng, hoàn lượt khuyến mãi)';

    public function handle(CurrentContext $context, PaymentService $payments): int
    {
        $expired = $context->runAs(ContextScope::system('vani:payment:expire'), function () use ($payments): int {
            $count = 0;
            Payment::query()->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Failed])->whereNotNull('expires_at')->where('expires_at', '<', now())
                ->orderBy('id')->chunkById(200, function ($batch) use ($payments, &$count): void {
                    foreach ($batch as $payment) {
                        $count += $payments->expire($payment->id) ? 1 : 0;
                    }
                });

            return $count;
        });

        $this->info("Đã huỷ {$expired} đơn hết hạn thanh toán.");

        return self::SUCCESS;
    }
}
