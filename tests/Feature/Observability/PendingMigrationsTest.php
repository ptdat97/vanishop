<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\PendingMigrations;

/*
| Code mới mà DB chưa chạy migration (thiếu bảng → trang lỗi 500): /health báo degraded, vani:security:check chặn, Tổng quan
| hiện "Migration chưa chạy" cho người quản trị cài đặt.
*/

it('DB đủ migration: không báo gì; thiếu một migration: /health degraded, security:check báo, Tổng quan có việc cần làm', function () {
    expect(app(PendingMigrations::class)->names())->toBe([]);
    $missing = (string) DB::table('migrations')->orderByDesc('id')->value('migration');
    DB::table('migrations')->where('migration', $missing)->delete();

    expect(app(PendingMigrations::class)->names())->toBe([$missing]);
    config(['vanishop.health.token' => 'health-token']);
    $this->getJson('/health', ['X-Health-Token' => 'health-token'])->assertJsonPath('checks.migrations.status', 'degraded')->assertJsonPath('checks.migrations.detail', '1 migration chưa chạy');
    $this->artisan('vani:security:check')->expectsOutputToContain('Còn migration chưa chạy');

    $tasks = fn () => collect($this->get('/admin')->assertOk()->viewData('page')['props']['tasks'])->keyBy('key');
    $this->actingAs(T::staff(['admin.access', 'settings.manage']), 'staff');
    expect($tasks()->get('system.pending_migrations'))->toMatchArray(['count' => 1, 'label' => 'Migration chưa chạy', 'tone' => 'danger']);
    $this->actingAs(T::staff(['admin.access', 'orders.view']), 'staff');
    expect($tasks()->has('system.pending_migrations'))->toBeFalse();
});
