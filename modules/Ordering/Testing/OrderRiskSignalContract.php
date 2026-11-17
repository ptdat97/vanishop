<?php

declare(strict_types=1);

namespace Modules\Ordering\Testing;

use Closure;
use Modules\Ordering\Contracts\Data\RiskSignal;
use Modules\Ordering\Contracts\OrderRiskSignal;

/**
 * Contract test cho OrderRiskSignal. Kiểm tra: danh sách rỗng → rỗng; chỉ trả đơn được hỏi; mỗi đơn là danh sách
 * RiskSignal có mức hợp lệ và nhãn; xác định. Bên gọi tạo đơn mẫu, ít nhất một đơn có tín hiệu.
 */
final class OrderRiskSignalContract
{
    /**
     * @param  Closure(): OrderRiskSignal  $signal
     * @param  Closure(): list<int>  $orderIds
     */
    public static function define(string $label, Closure $signal, Closure $orderIds): void
    {
        describe("OrderRiskSignal contract: {$label}", function () use ($signal, $orderIds): void {
            it('chỉ trả đơn được hỏi; RiskSignal hợp lệ; xác định', function () use ($signal, $orderIds): void {
                $ids = $orderIds();
                expect($signal()->assess([]))->toBe([]);
                $assessed = $signal()->assess($ids);
                expect($assessed)->not->toBe([])
                    ->and(array_diff(array_keys($assessed), $ids))->toBe([])
                    ->and($signal()->assess($ids))->toEqual($assessed);
                foreach ($assessed as $signals) {
                    expect($signals)->toBeArray()->and($signals)->not->toBe([]);
                    foreach ($signals as $risk) {
                        expect($risk)->toBeInstanceOf(RiskSignal::class)
                            ->and($risk->level)->toBeIn(RiskSignal::LEVELS)
                            ->and($risk->label)->not->toBe('');
                    }
                }
            });
        });
    }
}
