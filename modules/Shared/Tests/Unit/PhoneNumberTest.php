<?php

use Modules\Shared\Domain\Phone\InvalidPhoneNumber;
use Modules\Shared\Domain\Phone\PhoneNumber;

it('chuẩn hoá số Việt Nam về E.164', function (string $input, string $e164) {
    expect(PhoneNumber::fromString($input)->e164)->toBe($e164);
})->with([
    ['0912345678', '+84912345678'],
    ['84912345678', '+84912345678'],
    ['+84 912 345 678', '+84912345678'],
    ['(+84) 912-345-678', '+84912345678'],
    ['0387654321', '+84387654321'],
    ['02438123456', '+842438123456'],
]);

it('từ chối số không hợp lệ', function (string $input) {
    expect(PhoneNumber::tryFromString($input))->toBeNull();
    PhoneNumber::fromString($input);
})->with(['12345', '0112345678', '091234567', '+1 202 555 0100', 'abc'])->throws(InvalidPhoneNumber::class);

it('hiển thị dạng trong nước và dạng che', function () {
    $phone = PhoneNumber::fromString('+84912345678');

    expect($phone->national())->toBe('0912345678')
        ->and($phone->masked())->toBe('091****678')
        ->and($phone->equals(PhoneNumber::fromString('0912 345 678')))->toBeTrue();
});
