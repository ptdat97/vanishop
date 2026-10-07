<?php

declare(strict_types=1);

namespace Modules\Payment\Console;

use Illuminate\Console\Command;
use Modules\Payment\Application\PaymentVerifier;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Đối soát khoản đã thu với cổng (hằng ngày, chỉ báo cáo). Exit 1 khi có chênh lệch mới.
 */
final class VerifyPaymentsCommand extends Command
{
    protected $signature = 'vani:payment:verify {--days=7 : Khoản đã thu cập nhật trong N ngày qua}';

    protected $description = 'Đối soát khoản đã thu với cổng thanh toán (chỉ ghi chênh lệch, không tự sửa)';

    public function handle(PaymentVerifier $verifier, CurrentContext $context): int
    {
        $result = $context->runAs(ContextScope::system('vani:payment:verify'), fn (): array => $verifier->verify(max(1, (int) $this->option('days'))));
        $this->info("Đã đối chiếu {$result['checked']} khoản, bỏ qua {$result['skipped']}, chênh lệch mới {$result['issues']} (phiên #{$result['id']}).");

        return $result['issues'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
