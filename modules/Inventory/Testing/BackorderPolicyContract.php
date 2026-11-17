<?php

declare(strict_types=1);

namespace Modules\Inventory\Testing;

use Closure;
use Modules\Inventory\Contracts\BackorderPolicy;
use Modules\Inventory\Contracts\Data\BackorderAllowance;

/**
 * Contract test cho BackorderPolicy (ADR-047). Kiểm tra: danh sách rỗng → rỗng; chỉ trả SKU được hỏi; hạn mức > 0, ngày
 * dự kiến null hoặc Y-m-d; xác định. Bên gọi chuẩn bị dữ liệu để ít nhất một SKU có hạn mức.
 */
final class BackorderPolicyContract
{
    /**
     * @param  Closure(): BackorderPolicy  $policy
     * @param  Closure(): list<int>  $variantIds
     */
    public static function define(string $label, Closure $policy, Closure $variantIds): void
    {
        describe("BackorderPolicy contract: {$label}", function () use ($policy, $variantIds): void {
            it('chỉ trả SKU được hỏi; hạn mức > 0; ngày Y-m-d; xác định', function () use ($policy, $variantIds): void {
                $ids = $variantIds();
                expect($policy()->allowances([]))->toBe([]);
                $allowances = $policy()->allowances($ids);
                expect($allowances)->not->toBe([])
                    ->and(array_diff(array_keys($allowances), $ids))->toBe([])
                    ->and($policy()->allowances($ids))->toEqual($allowances);
                foreach ($allowances as $allowance) {
                    expect($allowance)->toBeInstanceOf(BackorderAllowance::class)
                        ->and($allowance->limit)->toBeGreaterThan(0);
                    if ($allowance->expectedOn !== null) {
                        expect($allowance->expectedOn)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
                    }
                }
            });
        });
    }
}
