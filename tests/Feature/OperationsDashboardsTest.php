<?php

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Identity\Persistence\Models\StaffUser;

it('Horizon và Pulse nằm dưới đường dẫn Admin, cần đăng nhập nhân viên và quyền system.monitor', function (string $path) {
    $this->get($path)->assertRedirect(route('admin.login'));

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff')->get($path)->assertForbidden();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'system.monitor'])->create(), 'staff')->get($path)->assertOk();
})->with(['horizon' => '/admin/system/horizon', 'pulse' => '/admin/system/pulse']);

it('không còn đường dẫn mặc định /horizon, /pulse', function () {
    $this->get('/horizon')->assertNotFound();
    $this->get('/pulse')->assertNotFound();
});

it('lịch hằng đêm chạy theo giờ Việt Nam (backup 02:00 VN = 19:00 UTC), không theo UTC của app', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-08 19:00:00', 'UTC'));
    $due = collect(app(Schedule::class)->dueEvents($this->app))->map(fn ($event) => (string) $event->command);

    expect($due->contains(fn (string $command): bool => str_contains($command, 'backup:run')))->toBeTrue()
        ->and($due->contains(fn (string $command): bool => str_contains($command, 'vani:payment:verify')))->toBeFalse();
});
