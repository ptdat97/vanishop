<?php

use Symfony\Component\Finder\Finder;

/*
| ADR-046 chốt ②: đặt vận đơn ở hãng chỉ đi qua một lệnh — `ShipmentBooking::pushToCarrier` (nút, hàng loạt, plugin,
| `booking = on_create`). Không đường tắt nào dispatch `BookShipmentJob` trực tiếp, để mặc định "nhân viên bấm" không bị
| một listener hay plugin vượt qua mà không đi qua cùng kiểm tra trạng thái/hãng.
*/

it('BookShipmentJob chỉ được dispatch trong FulfillmentService::pushToCarrier', function () {
    $offenders = [];
    $files = Finder::create()->files()->name('*.php')->in([base_path('modules'), base_path('custom/plugin')])->notPath('#/Tests/#');
    foreach ($files as $file) {
        $source = $file->getContents();
        if (! str_contains($source, 'BookShipmentJob::dispatch')) {
            continue;
        }
        $relative = str_replace(base_path().'/', '', $file->getRealPath());
        if ($relative !== 'modules/Fulfillment/Application/FulfillmentService.php') {
            $offenders[] = $relative;

            continue;
        }
        preg_match('/public function pushToCarrier\(.*?\n    \}\n/s', $source, $method);
        if (substr_count($source, 'BookShipmentJob::dispatch') !== substr_count($method[0] ?? '', 'BookShipmentJob::dispatch')) {
            $offenders[] = "{$relative} (ngoài pushToCarrier)";
        }
    }

    expect($offenders)->toBe([], 'Gọi ShipmentBooking::pushToCarrier thay vì dispatch BookShipmentJob trực tiếp');
});
