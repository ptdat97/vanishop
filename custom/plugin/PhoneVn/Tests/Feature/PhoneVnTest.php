<?php

use Illuminate\Support\Facades\DB;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Shared\Application\Phone\InternationalPhonePolicy;
use Modules\Shared\Contracts\PhoneNumberPolicy;
use Modules\Shared\Domain\Phone\InvalidPhoneNumber;
use Modules\Shared\Support\Phones;
use Plugin\PhoneVn\Infrastructure\VietnamPhonePolicy;

it('là luật số điện thoại đang dùng (plugin hệ thống, VANI_PHONE_POLICY=vn)', function () {
    expect(app(PhoneNumberPolicy::class))->toBeInstanceOf(VietnamPhonePolicy::class);
});

it('chuẩn hoá số Việt Nam về E.164', function (string $input, string $e164) {
    expect(Phones::fromString($input)->e164)->toBe($e164);
})->with([
    ['0912345678', '+84912345678'],
    ['84912345678', '+84912345678'],
    ['+84 912 345 678', '+84912345678'],
    ['(+84) 912-345-678', '+84912345678'],
    ['0387654321', '+84387654321'],
    ['02438123456', '+842438123456'],
]);

it('từ chối số không hợp lệ', function (string $input) {
    expect(Phones::parse($input))->toBeNull();
    Phones::fromString($input);
})->with(['12345', '0112345678', '091234567', '+1 202 555 0100', 'abc'])->throws(InvalidPhoneNumber::class);

it('hiển thị dạng trong nước và dạng che', function () {
    $phone = Phones::fromString('+84912345678');

    expect($phone->national())->toBe('0912345678')
        ->and($phone->masked())->toBe('091****678')
        ->and($phone->equals(Phones::fromString('0912 345 678')))->toBeTrue();
});

it('tắt plugin hoặc cấu hình mã khác → Core dùng `international`: chỉ nhận số dạng quốc tế', function () {
    config(['vanishop.locale.phone_policy' => 'international']);
    expect(Phones::parse('0912345678'))->toBeNull()
        ->and(Phones::parse('+84 912 345 678')?->e164)->toBe('+84912345678');

    config(['vanishop.locale.phone_policy' => 'vn']);
    DB::table('plugins')->where('id', 'vani.phone-vn')->update(['status' => 'disabled']);
    PluginActivation::forgetCache();
    app(PluginActivation::class)->flush();
    expect(app(PhoneNumberPolicy::class))->toBeInstanceOf(InternationalPhonePolicy::class)
        ->and(Phones::parse('0912345678'))->toBeNull();
});
