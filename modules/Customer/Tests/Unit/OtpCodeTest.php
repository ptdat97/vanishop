<?php

use Modules\Customer\Domain\OtpCode;

it('sinh mã 6 chữ số, giữ số 0 đầu', function () {
    foreach (range(1, 50) as $ignored) {
        expect(OtpCode::isWellFormed(OtpCode::generate()))->toBeTrue();
    }
    expect(OtpCode::isWellFormed('01234'))->toBeFalse()->and(OtpCode::isWellFormed('12345a'))->toBeFalse();
});

it('hash gắn với SĐT và khoá bí mật', function () {
    expect(OtpCode::hash('k', '+84912345678', '123456'))->not->toBe(OtpCode::hash('k', '+84912345679', '123456'))
        ->and(OtpCode::hash('k', '+84912345678', '123456'))->not->toBe(OtpCode::hash('k2', '+84912345678', '123456'));
});
