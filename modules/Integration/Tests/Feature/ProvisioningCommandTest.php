<?php

use Illuminate\Validation\ValidationException;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Integration\Persistence\Models\IntegrationClient;

it('tạo client + key, cập nhật scope, xoay vòng và thu hồi key, đăng ký webhook', function () {
    $this->artisan('vani:integration:client', ['code' => 'erp-main', '--name' => 'ERP', '--scope' => ['orders:read', 'inventory:write']])
        ->expectsOutputToContain('Key ID: vk_')
        ->expectsOutputToContain('Secret: ')
        ->assertSuccessful();

    $client = IntegrationClient::query()->where('code', 'erp-main')->sole();
    expect($client->scopes)->toBe(['orders:read', 'inventory:write'])
        ->and($client->keys()->count())->toBe(1);

    // Cập nhật không cấp key mới; secret được mã hoá trong DB.
    $this->artisan('vani:integration:client', ['code' => 'erp-main', '--scope' => ['events:read']])->doesntExpectOutputToContain('Secret:')->assertSuccessful();
    $key = $client->keys()->sole();
    expect($client->fresh()->scopes)->toBe(['events:read'])
        ->and($key->getRawOriginal('secret'))->not->toBe($key->secret);

    $this->artisan('vani:integration:client', ['code' => 'erp-main', '--new-key' => true])->expectsOutputToContain('Key ID:')->assertSuccessful();
    $this->artisan('vani:integration:client', ['code' => 'erp-main', '--revoke' => $key->key_id, '--new-key' => true])->assertSuccessful();
    expect($client->keys()->whereNull('revoked_at')->count())->toBe(2);

    $this->artisan('vani:integration:webhook', ['client' => 'erp-main', 'url' => 'https://erp.example/hooks', '--event' => ['order.*']])
        ->expectsOutputToContain('Signing secret:')->assertSuccessful();
    expect($client->subscriptions()->sole()->event_types)->toBe(['order.*'])
        ->and(AuditLog::query()->where('action', 'like', 'integration.%')->count())->toBeGreaterThanOrEqual(6);
});

it('từ chối mã client "vanishop" và scope lạ', function () {
    expect(fn () => $this->artisan('vani:integration:client', ['code' => 'vanishop'])->run())->toThrow(ValidationException::class)
        ->and(fn () => $this->artisan('vani:integration:client', ['code' => 'erp', '--scope' => ['admin:*']])->run())->toThrow(ValidationException::class);
});
