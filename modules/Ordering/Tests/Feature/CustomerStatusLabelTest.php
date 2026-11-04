<?php

use Illuminate\Support\Facades\App;
use Modules\Ordering\Application\CustomerStatusLabel;

it('nhãn trạng thái cho khách theo ngôn ngữ của request', function () {
    expect(CustomerStatusLabel::of('pending', 'cod_pending', 'unfulfilled', 'none'))->toBe(['code' => 'awaiting_confirmation', 'label' => 'Chờ xác nhận']);

    App::setLocale('en');
    expect(CustomerStatusLabel::of('processing', 'paid', 'shipped', 'none'))->toBe(['code' => 'shipping', 'label' => 'Shipping']);
});
