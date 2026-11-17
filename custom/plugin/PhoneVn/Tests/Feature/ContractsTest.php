<?php

use Modules\Shared\Testing\PhoneNumberPolicyContract;
use Plugin\PhoneVn\Infrastructure\VietnamPhonePolicy;

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

// Plugin hệ thống (bundled): đã cài + bật sẵn trong môi trường test.

PhoneNumberPolicyContract::define('vani.phone-vn', fn () => app(VietnamPhonePolicy::class), ['0912345678', '+84912345678', '0912 345 678', '84912345678']);
