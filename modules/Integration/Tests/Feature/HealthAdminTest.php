<?php

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Integration\Application\OutboxWorker;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Tests\Feature\IntegrationTestHelpers as H;

require_once __DIR__.'/IntegrationTestHelpers.php';

beforeEach(function () {
    H::client('erp-main');
    [$this->subscription] = H::subscription('erp-main', ['*'], 'https://erp.example/hooks');
    Http::fake(['erp.example/*' => Http::response('bad', 400)]);
    H::publish('order.created', 'LU-1', ['order' => ['customer' => ['full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678']]]);
    app(OutboxWorker::class)->run();
});

it('Owner xem message lỗi (payload đã che PII), client và webhook; replay được', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'integration.view', 'integration.replay', 'integration.manage'])->create(), 'staff');

    $this->get('/admin/integration')->assertInertia(fn (Assert $page) => $page->component('Integration::Health/Index')
        ->where('summary.outbox.failed', 1)
        ->has('messages', 1)
        ->where('messages.0.last_error', 'http 400')
        ->where('messages.0.payload.data.order.customer.phone', '09******78')
        ->where('clients.0.code', 'erp-main')
        ->where('clients.0.active_keys', 1)
        ->where('clients.0.subscriptions.0.url', 'https://erp.example/hooks')
        ->where('can.replay', true));

    $id = OutboxRecord::query()->value('id');
    $this->post('/admin/integration/replay', ['box' => 'outbox', 'ids' => [$id]])->assertSessionHasNoErrors();
    expect(OutboxRecord::query()->sole()->status->value)->toBe('pending');

    $this->subscription->update(['status' => 'paused']);
    $this->post("/admin/integration/subscriptions/{$this->subscription->id}/resume")->assertSessionHasNoErrors();
    expect($this->subscription->fresh()->status)->toBe('active');
});

it('chỉ xem không replay được; không có quyền xem thì bị chặn', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'integration.view'])->create(), 'staff');
    $this->get('/admin/integration')->assertOk();
    $this->post('/admin/integration/replay', ['box' => 'outbox'])->assertForbidden();

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
    $this->get('/admin/integration')->assertForbidden();
});
