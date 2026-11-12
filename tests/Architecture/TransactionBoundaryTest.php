<?php

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/*
| Ranh giới transaction (stabilization C3):
| 1. Domain event chỉ phát khi transaction đã commit — listener (hoàn tiền, nhả hàng, tích hợp…) không thấy dữ liệu sẽ bị
|    rollback.
| 2. Lời gọi ra dịch vụ ngoài của Core đi sau ExternalCalls::guard() — guard ném lỗi khi lời gọi nằm trong transaction
|    chưa commit (dev/test), nên cả bộ test kiểm quy tắc này trên luồng thật.
*/

/** Event được phép phát ngay (kèm lý do). */
const EVENTS_DISPATCHED_IMMEDIATELY = [
    'Modules\\Catalog\\Events\\ProductViewed' => 'phát ngoài transaction (beacon lượt xem), không có dữ liệu để rollback',
];

/** Lời gọi ra ngoài được phép nằm trong transaction (kèm lý do, có backlog). */
const EXTERNAL_CALLS_IN_TRANSACTION = [
    'modules/Fulfillment/Application/FulfillmentService.php:createShipment' => 'khoá vận đơn trong lúc đặt với hãng để hai worker không đặt trùng (backlog L31)',
];

it('mọi domain event của Core phát sau khi transaction commit', function () {
    $immediate = [];
    foreach (glob(base_path('modules/*/Events/*.php')) as $file) {
        $class = 'Modules\\'.basename(dirname($file, 2)).'\\Events\\'.basename($file, '.php');
        if (class_exists($class) && ! is_subclass_of($class, ShouldDispatchAfterCommit::class) && ! array_key_exists($class, EVENTS_DISPATCHED_IMMEDIATELY)) {
            $immediate[] = $class;
        }
    }

    expect($immediate)->toBe([], 'Implement ShouldDispatchAfterCommit, hoặc ghi lý do vào EVENTS_DISPATCHED_IMMEDIATELY');
});

it('lời gọi cổng thanh toán, hãng vận chuyển, kênh OTP trong Core đi sau ExternalCalls::guard()', function () {
    $unguarded = [];
    $found = 0;
    foreach (glob(base_path('modules/*/Application/{,*/}*.php'), GLOB_BRACE) as $file) {
        $lines = file($file);
        foreach ($lines as $index => $line) {
            if (preg_match('/\$(gateway|carrier|sender)\??->(initiate|capture|void|refund|cancel|send|createShipment)\(/', $line, $match) !== 1) {
                continue;
            }
            $found++;
            $relative = str_replace(base_path().'/', '', $file);
            $context = implode('', array_slice($lines, max(0, $index - 3), 4));
            if (! str_contains($context, 'ExternalCalls::guard(') && ! array_key_exists("{$relative}:{$match[2]}", EXTERNAL_CALLS_IN_TRANSACTION)) {
                $unguarded[] = "{$relative}:".($index + 1)." {$match[1]}->{$match[2]}";
            }
        }
    }

    expect($found)->toBeGreaterThanOrEqual(7, 'Không thấy lời gọi ra ngoài nào — mẫu tìm kiếm đã lỗi thời?')
        ->and($unguarded)->toBe([], 'Gọi ExternalCalls::guard() ngay trước lời gọi ra ngoài');
});
