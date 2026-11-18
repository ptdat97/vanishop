<?php

use Modules\Payment\Domain\CodRules;
use Modules\Payment\Domain\InvalidPaymentTransition;
use Modules\Payment\Domain\PaymentAmount;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Domain\RefundDecision;
use Modules\Payment\Domain\RefundRules;
use Modules\Payment\Domain\RefundStatus;
use Modules\Shared\Domain\Money\Money;

/*
| Domain Payment thuần PHP: máy trạng thái, luật hoàn tiền, COD, khớp số tiền — không DB.
*/

it('máy trạng thái: chỉ chuyển theo bảng; trạng thái cuối không đi tiếp', function () {
    expect(PaymentStatus::Pending->transitionTo(PaymentStatus::Paid))->toBe(PaymentStatus::Paid)
        ->and(PaymentStatus::Expired->canTransitionTo(PaymentStatus::Paid))->toBeTrue()          // nhân viên xác nhận chuyển khoản muộn
        ->and(PaymentStatus::Authorized->canTransitionTo(PaymentStatus::Cancelled))->toBeTrue()
        ->and(PaymentStatus::Paid->canTransitionTo(PaymentStatus::Cancelled))->toBeFalse()       // đã thu thì phải hoàn, không huỷ
        ->and(PaymentStatus::Refunded->canTransitionTo(PaymentStatus::Paid))->toBeFalse()
        ->and(PaymentStatus::Cancelled->canTransitionTo(PaymentStatus::Paid))->toBeTrue()                 // tiền về sau khi huỷ theo đơn → ghi nhận rồi hoàn
        ->and(fn () => PaymentStatus::Refunded->transitionTo(PaymentStatus::Paid))->toThrow(InvalidPaymentTransition::class);
});

it('câu hỏi nghiệp vụ theo trạng thái', function () {
    expect(PaymentStatus::Refunded->everCollected())->toBeTrue()->and(PaymentStatus::Refunded->hasCollected())->toBeFalse()
        ->and(PaymentStatus::Failed->canRetry())->toBeTrue()->and(PaymentStatus::Expired->canRetry())->toBeFalse()
        ->and(PaymentStatus::Expired->canConfirmManually())->toBeTrue()->and(PaymentStatus::Paid->canConfirmManually())->toBeFalse()
        ->and(PaymentStatus::Failed->closesWithoutCollection())->toBeTrue()->and(PaymentStatus::Authorized->closesWithoutCollection())->toBeFalse();
});

it('hoàn tiền: hết phần còn lại thì bỏ qua; chưa thu thì từ chối; vượt số còn hoàn được thì từ chối; tự động khi cổng hỗ trợ', function () {
    expect(RefundRules::decide(PaymentStatus::Paid, 300_000, 0, null))->toEqual(RefundDecision::accept(300_000, 300_000))
        ->and(RefundRules::decide(PaymentStatus::Refunded, 300_000, 300_000, null)->outcome)->toBe(RefundDecision::SKIP)
        ->and(RefundRules::decide(PaymentStatus::Pending, 300_000, 0, null)->outcome)->toBe(RefundDecision::SKIP)
        ->and(RefundRules::decide(PaymentStatus::Pending, 300_000, 0, 1_000)->outcome)->toBe(RefundDecision::NOT_COLLECTED)
        ->and(RefundRules::decide(PaymentStatus::PartiallyRefunded, 300_000, 200_000, 150_000))->toEqual(RefundDecision::exceeds(100_000))
        ->and(RefundRules::decide(PaymentStatus::Paid, 300_000, 0, 0)->outcome)->toBe(RefundDecision::EXCEEDS)
        ->and(RefundRules::statusAfterRefund(300_000, 100_000))->toBe(PaymentStatus::PartiallyRefunded)
        ->and(RefundRules::isAutomatic(true, false, 100_000, 300_000))->toBeFalse()   // hoàn một phần, cổng chỉ hoàn toàn phần
        ->and(RefundRules::isAutomatic(true, false, 300_000, 300_000))->toBeTrue()
        ->and(RefundRules::isAutomatic(false, true, 300_000, 300_000))->toBeFalse();
});

it('khoản hoàn: ban đầu theo cách hoàn; chỉ ghi nhận đã chuyển trả khi đang chờ hoặc đang chuyển', function () {
    expect(RefundStatus::initial(true))->toBe(RefundStatus::Processing)->and(RefundStatus::initial(false))->toBe(RefundStatus::Requested)
        ->and(RefundStatus::PayingOut->canComplete())->toBeTrue()
        ->and(RefundStatus::Processing->canComplete())->toBeFalse()
        ->and(RefundStatus::Completed->canComplete())->toBeFalse();
});

it('COD và khớp số tiền', function () {
    expect(CodRules::canCollectManually(PaymentStatus::Pending, 330_000, true))->toBeTrue()
        ->and(CodRules::canCollectManually(PaymentStatus::Pending, 0, true))->toBeFalse()
        ->and(CodRules::canCollectManually(PaymentStatus::Pending, 330_000, false))->toBeFalse()
        ->and(CodRules::amountAfterPartialCancellation(100_000, 150_000))->toBe(0)
        ->and(PaymentAmount::matches(330_000, 'VND', Money::vnd(330_000)))->toBeTrue()
        ->and(PaymentAmount::matches(330_000, 'VND', Money::vnd(300_000)))->toBeFalse();
});
