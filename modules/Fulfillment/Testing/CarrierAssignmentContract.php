<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Testing;

use Closure;
use Modules\Fulfillment\Contracts\CarrierAssignment;
use Modules\Fulfillment\Contracts\Data\CarrierAssignmentContext;
use Modules\Fulfillment\Contracts\Data\CarrierChoice;

/**
 * Contract test cho CarrierAssignment. Kiểm tra: với mỗi ngữ cảnh mẫu trả null (không quyết) hoặc CarrierChoice có mã hãng;
 * xác định. Bên gọi cấu hình quy tắc để có ít nhất một ngữ cảnh được chọn hãng.
 */
final class CarrierAssignmentContract
{
    /**
     * @param  Closure(): CarrierAssignment  $assignment
     * @param  Closure(): list<CarrierAssignmentContext>  $contexts
     */
    public static function define(string $label, Closure $assignment, Closure $contexts): void
    {
        describe("CarrierAssignment contract: {$label}", function () use ($assignment, $contexts): void {
            it('null hoặc hãng có mã; ít nhất một ngữ cảnh được chọn; xác định', function () use ($assignment, $contexts): void {
                $chosen = 0;
                foreach ($contexts() as $context) {
                    $choice = $assignment()->assign($context);
                    expect($choice === null || $choice instanceof CarrierChoice)->toBeTrue()
                        ->and($assignment()->assign($context))->toEqual($choice);
                    if ($choice !== null) {
                        expect($choice->carrierCode)->toMatch('/^[a-z][a-z0-9_.-]*$/');
                        $chosen++;
                    }
                }
                expect($chosen)->toBeGreaterThan(0);
            });
        });
    }
}
