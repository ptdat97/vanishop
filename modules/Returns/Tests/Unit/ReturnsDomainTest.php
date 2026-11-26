<?php

use Modules\Returns\Domain\ExchangeValuation;
use Modules\Returns\Domain\ReturnCondition;
use Modules\Returns\Domain\ReturnLimits;
use Modules\Returns\Domain\ReturnProgress;
use Modules\Returns\Domain\ReturnStatus;

/*
| Domain Returns thuần PHP: giới hạn số lượng/tiền, tiến độ đổi trả của đơn, tình trạng
| hàng trả, giá trị đổi hàng — không DB.
*/

it('mỗi dòng trả tối đa số đã giao trừ số đang/đã trả; tiền hoàn trong [0, số tính được]', function () {
    $delivered = [10 => 2, 11 => 1];
    $taken = [10 => 1];

    expect(ReturnLimits::remaining($delivered, $taken))->toBe([10 => 1, 11 => 1])
        ->and(ReturnLimits::firstExceeded([10 => 1, 11 => 1], $delivered, $taken))->toBeNull()
        ->and(ReturnLimits::firstExceeded([11 => 1, 10 => 2], $delivered, $taken))->toBe([10, 1])
        ->and(ReturnLimits::firstExceeded([99 => 1], $delivered, $taken))->toBe([99, 0])   // dòng chưa giao
        ->and(ReturnLimits::refundWithinLimit(0, 100_000))->toBeTrue()
        ->and(ReturnLimits::refundWithinLimit(100_001, 100_000))->toBeFalse()
        ->and(ReturnLimits::refundWithinLimit(-1, 100_000))->toBeFalse();
});

it('tiến độ đổi trả của đơn', function () {
    expect(ReturnProgress::forOrder([ReturnStatus::Requested], 0, 3))->toBe('requested')
        ->and(ReturnProgress::forOrder([ReturnStatus::Requested, ReturnStatus::Approved], 0, 3))->toBe('in_progress')
        ->and(ReturnProgress::forOrder([ReturnStatus::Rejected], 0, 3))->toBe('none')
        ->and(ReturnProgress::forOrder([ReturnStatus::Resolved], 1, 3))->toBe('partially_returned')
        ->and(ReturnProgress::forOrder([ReturnStatus::Resolved, ReturnStatus::Cancelled], 3, 3))->toBe('returned');
});

it('trạng thái và tình trạng hàng trả', function () {
    expect(ReturnStatus::Approved->acceptsAttachments())->toBeTrue()->and(ReturnStatus::InTransit->acceptsAttachments())->toBeFalse()
        ->and(ReturnStatus::Requested->customerCancellable())->toBeTrue()->and(ReturnStatus::Approved->customerCancellable())->toBeFalse()
        ->and(ReturnCondition::fromInput(null))->toBe(ReturnCondition::Sellable)
        ->and(ReturnCondition::fromInput('lạ'))->toBe(ReturnCondition::Sellable)
        ->and(ReturnCondition::fromInput('damaged')->restocks())->toBeFalse();
});

it('đổi hàng: cùng mẫu giữ giá đã mua; khác mẫu bù trừ với số đã trả, chia theo tỷ lệ, khách bù phần thiếu hoặc được hoàn phần thừa', function () {
    // Cùng mẫu: không chênh.
    expect(ExchangeValuation::quote([1 => ['quantity' => 2, 'unit_amount' => 150_000, 'same_product' => true, 'paid' => 300_000]]))
        ->toBe(['credits' => [1 => 300_000], 'payable' => 0, 'refund' => 0]);

    // Khác mẫu, đắt hơn: đã trả 300k, mẫu mới 2 × 200k = 400k → khách bù 100k; credit chia 3:1 theo thành tiền.
    expect(ExchangeValuation::quote([
        1 => ['quantity' => 1, 'unit_amount' => 300_000, 'same_product' => false, 'paid' => 200_000],
        2 => ['quantity' => 1, 'unit_amount' => 100_000, 'same_product' => false, 'paid' => 100_000],
    ]))->toBe(['credits' => [1 => 225_000, 2 => 75_000], 'payable' => 100_000, 'refund' => 0]);

    // Khác mẫu, rẻ hơn: đã trả 300k, mẫu mới 250k → hoàn 50k, credit bằng giá mẫu mới; tổng credit đúng bằng giá trị.
    $cheaper = ExchangeValuation::quote([1 => ['quantity' => 1, 'unit_amount' => 250_000, 'same_product' => false, 'paid' => 300_000]]);
    expect($cheaper)->toBe(['credits' => [1 => 250_000], 'payable' => 0, 'refund' => 50_000]);

    // Làm tròn: phần dư sau chia nguyên được rải vào dòng đầu, tổng credit không lệch.
    $odd = ExchangeValuation::quote([
        1 => ['quantity' => 1, 'unit_amount' => 100_001, 'same_product' => false, 'paid' => 100_000],
        2 => ['quantity' => 1, 'unit_amount' => 100_002, 'same_product' => false, 'paid' => 100_000],
    ]);
    expect(array_sum($odd['credits']))->toBe(200_000)->and($odd['payable'])->toBe(3);
});
