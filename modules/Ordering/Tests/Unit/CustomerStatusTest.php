<?php

use Modules\Ordering\Domain\CustomerStatus;

it('tính nhãn cho khách từ 4 chiều trạng thái', function (array $dimensions, string $code) {
    expect(CustomerStatus::of(...$dimensions)['code'])->toBe($code);
})->with([
    'COD chờ xác nhận' => [['pending', 'cod_pending', 'unfulfilled', 'none', 'cod'], 'awaiting_confirmation'],
    'online chờ thanh toán' => [['pending', 'unpaid', 'unfulfilled', 'none', 'vnpay'], 'awaiting_payment'],
    'thanh toán lỗi vẫn chờ' => [['pending', 'failed', 'unfulfilled', 'none', 'vnpay'], 'awaiting_payment'],
    'đã xác nhận' => [['confirmed', 'paid', 'unfulfilled', 'none', 'vnpay'], 'preparing'],
    'đang giao' => [['processing', 'cod_pending', 'shipped', 'none', 'cod'], 'shipping'],
    'đã giao' => [['processing', 'cod_collected', 'delivered', 'none', 'cod'], 'delivered'],
    'hoàn tất' => [['completed', 'paid', 'delivered', 'none', 'vnpay'], 'completed'],
    'đang trả' => [['completed', 'paid', 'delivered', 'requested', 'vnpay'], 'returning'],
    'huỷ thắng mọi chiều' => [['cancelled', 'refunded', 'unfulfilled', 'none', 'vnpay'], 'cancelled'],
]);
