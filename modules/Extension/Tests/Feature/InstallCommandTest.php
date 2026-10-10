<?php

use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

const BUNDLED = ['vani.bank-transfer', 'vani.cod', 'vani.phone-vn', 'vani.provinces-vn', 'vani.shipping-flat-rate', 'vani.tax-vn-vat'];

beforeEach(function () {
    app(CurrentContext::class)->set(ContextScope::system('test'));
    // Hệ thống mới: chưa cài plugin nào.
    PluginRecord::query()->delete();
    PluginActivation::forgetCache();
    app(PluginActivation::class)->flush();
});

it('doctor báo extension point bắt buộc thiếu implementation khi chưa cài plugin hệ thống', function () {
    $missing = collect(app(PluginDoctor::class)->diagnose())->where('code', 'required_extension_missing');

    expect($missing->pluck('plugin')->unique()->all())->toBe(['core'])
        ->and($missing->pluck('message')->implode(' '))->toContain('vani.payment.gateways')->toContain('vani.checkout.shipping_providers')
        // Hệ thống đang chạy được nâng Core có plugin hệ thống mới: doctor nhắc chạy vani:install.
        ->and(collect(app(PluginDoctor::class)->diagnose())->where('code', 'bundled_not_installed')->pluck('plugin')->sort()->values()->all())->toBe(BUNDLED);
});

it('vani:install cài + bật 4 plugin hệ thống, chạy lại không đổi gì; plugin đã tắt có chủ đích giữ nguyên', function () {
    $this->artisan('vani:install', ['--no-migrate' => true])
        ->expectsOutputToContain('Đã cài + bật: vani.bank-transfer, vani.cod, vani.phone-vn, vani.provinces-vn, vani.shipping-flat-rate, vani.tax-vn-vat.')
        ->assertSuccessful();

    expect(PluginRecord::query()->where('status', PluginStatus::Enabled)->orderBy('id')->pluck('id')->all())->toBe(BUNDLED)
        ->and(collect(app(PluginDoctor::class)->diagnose())->where('code', 'required_extension_missing')->all())->toBe([]);

    PluginRecord::query()->whereKey('vani.tax-vn-vat')->update(['status' => PluginStatus::Disabled]);
    $this->artisan('vani:install', ['--no-migrate' => true])->expectsOutputToContain('đã được cài từ trước')->assertSuccessful();
    expect(PluginRecord::query()->find('vani.tax-vn-vat')->status)->toBe(PluginStatus::Disabled);
});
