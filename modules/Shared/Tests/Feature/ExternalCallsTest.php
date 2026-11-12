<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Support\ExternalCalls;

/*
| Ranh giới transaction (stabilization C3): gọi dịch vụ ngoài trong transaction chưa commit → lỗi ở dev/test, cảnh báo ở
| production. Transaction bọc test (RefreshDatabase) không tính.
*/

it('ngoài transaction: cho gọi; trong transaction: ném lỗi (ngoài production)', function () {
    ExternalCalls::guard('payment.initiate:fake');

    expect(fn () => DB::transaction(fn () => ExternalCalls::guard('payment.initiate:fake')))
        ->toThrow(LogicException::class, 'payment.initiate:fake');
    DB::afterCommit(fn () => ExternalCalls::guard('payment.refund:fake'));
    DB::transaction(fn () => DB::afterCommit(fn () => ExternalCalls::guard('payment.refund:fake')));
});

it('production: không chặn luồng nghiệp vụ, chỉ ghi cảnh báo', function () {
    app()->detectEnvironment(fn (): string => 'production');
    Log::spy();

    DB::transaction(fn () => ExternalCalls::guard('carrier.cancel:fake'));

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message): bool => str_contains($message, 'carrier.cancel:fake'));
});
