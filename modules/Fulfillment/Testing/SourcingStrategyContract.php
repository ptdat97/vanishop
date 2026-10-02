<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Testing;

use Closure;
use Modules\Fulfillment\Contracts\Data\AllocationProposal;
use Modules\Fulfillment\Contracts\Data\SourcingRequest;
use Modules\Fulfillment\Contracts\SourcingStrategy;
use Modules\Inventory\Contracts\Data\ReservedLine;
use Modules\Ordering\Contracts\Data\OrderLineData;

/**
 * Contract test cho SourcingStrategy. Kiểm tra: mã ổn định; mỗi dòng đơn được phân bổ ĐÚNG số lượng đặt; chỉ dùng
 * location đang giữ hàng và không vượt số đang giữ theo (location, variant); số lượng dương; xác định.
 * (Core cũng từ chối đề xuất lệch hàng đang giữ khi tạo vận đơn.)
 */
final class SourcingStrategyContract
{
    /**
     * @param  Closure(): SourcingStrategy  $strategy
     * @param  (Closure(): SourcingRequest)|null  $request
     */
    public static function define(string $label, Closure $strategy, ?Closure $request = null): void
    {
        $request ??= fn (): SourcingRequest => new SourcingRequest(
            1,
            [new OrderLineData(10, 101, 'SKU-1', 'Áo', 3), new OrderLineData(11, 102, 'SKU-2', 'Quần', 1)],
            [new ReservedLine(101, 1, 2), new ReservedLine(101, 2, 1), new ReservedLine(102, 2, 1)],
            ['province_code' => '79'],
        );

        describe("SourcingStrategy contract: {$label}", function () use ($strategy, $request): void {
            it('có mã ổn định', fn () => expect($strategy()->code())->toMatch('/^[a-z][a-z0-9_]*$/'));

            it('phân bổ đủ và đúng hàng đang giữ, xác định', function () use ($strategy, $request): void {
                $input = $request();
                $proposals = $strategy()->allocate($input);
                $variantOf = [];
                foreach ($input->lines as $line) {
                    $variantOf[$line->id] = $line->variantId;
                }
                $held = [];
                foreach ($input->reserved as $reserved) {
                    $held["{$reserved->locationId}:{$reserved->variantId}"] = ($held["{$reserved->locationId}:{$reserved->variantId}"] ?? 0) + $reserved->quantity;
                }

                $allocated = [];
                $used = [];
                foreach ($proposals as $proposal) {
                    expect($proposal)->toBeInstanceOf(AllocationProposal::class);
                    foreach ($proposal->lines as $lineId => $quantity) {
                        expect($quantity)->toBeGreaterThan(0)->and(isset($variantOf[$lineId]))->toBeTrue();
                        $allocated[$lineId] = ($allocated[$lineId] ?? 0) + $quantity;
                        $used["{$proposal->locationId}:{$variantOf[$lineId]}"] = ($used["{$proposal->locationId}:{$variantOf[$lineId]}"] ?? 0) + $quantity;
                    }
                }

                foreach ($input->lines as $line) {
                    expect($allocated[$line->id] ?? 0)->toBe($line->quantity);
                }
                foreach ($used as $key => $quantity) {
                    expect($quantity)->toBeLessThanOrEqual($held[$key] ?? 0);
                }
                expect($strategy()->allocate($request()))->toEqual($proposals);
            });
        });
    }
}
