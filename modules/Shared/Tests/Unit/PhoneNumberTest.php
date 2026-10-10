<?php

use Modules\Shared\Application\Phone\InternationalPhonePolicy;
use Modules\Shared\Domain\Phone\InvalidPhoneNumber;
use Modules\Shared\Domain\Phone\PhoneNumber;

it('PhoneNumber chỉ nhận E.164; che theo dạng trong nước', function () {
    $phone = PhoneNumber::of('+84912345678', '0912345678');

    expect($phone->masked())->toBe('091****678')
        ->and($phone->equals(PhoneNumber::of('+84912345678')))->toBeTrue()
        ->and(PhoneNumber::of('+12025550100')->national())->toBe('+12025550100');
    PhoneNumber::of('0912345678');
})->throws(InvalidPhoneNumber::class);

it('policy trung lập của Core chỉ nhận số dạng quốc tế, không đoán mã quốc gia', function (string $input, ?string $e164) {
    expect((new InternationalPhonePolicy)->normalize($input))->toBe($e164);
})->with([
    ['+84 912 345 678', '+84912345678'],
    ['0084912345678', '+84912345678'],
    ['+1 (202) 555-0100', '+12025550100'],
    ['0912345678', null],
    ['+0912345678', null],
    ['abc', null],
]);
