<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;

/*
| Vai trò mẫu cho từng team (Admin → Vai trò → Thêm): quyền tự khai mẫu khi đăng ký (Core và plugin); mẫu Phân tích chỉ
| xem, không thấy liên hệ khách; Vận hành đơn không hoàn tiền; Nội dung không sửa giá.
*/

beforeEach(function () {
    $owner = Role::query()->firstOrCreate(['code' => 'owner_admin'], ['name' => 'Owner Admin', 'is_system' => true]);
    $owner->syncPermissions([PermissionRegistry::WILDCARD]);
    $this->owner = StaffUser::factory()->create();
    $this->owner->roleAssignments()->create(['role_id' => $owner->id, 'scope_type' => ScopeType::Owner, 'scope_id' => null]);
    $this->presets = fn (): array => collect($this->actingAs($this->owner, 'staff')->get('/admin/staff/roles/create')->assertOk()->viewData('page')['props']['presets'])->keyBy('key')->all();
});

it('4 mẫu, quyền đúng vai trò của từng team; luôn có admin.access', function () {
    $presets = ($this->presets)();

    expect(array_keys($presets))->toBe(['order_ops', 'marketing', 'content', 'analyst'])
        ->and($presets['order_ops']['permissions'])->toContain('admin.access', 'orders.manage', 'orders.export', 'customers.pii', 'fulfillment.manage', 'returns.manage', 'payments.confirm', 'inventory.adjust')
        ->and($presets['order_ops']['permissions'])->not->toContain('payments.refund', 'returns.refund', 'orders.override_status', 'settings.manage')
        ->and($presets['marketing']['permissions'])->toContain('promotion.manage', 'customers.segment', 'notifications.manage')
        ->and($presets['marketing']['permissions'])->not->toContain('customers.pii', 'orders.export')
        ->and($presets['content']['permissions'])->toContain('catalog.manage', 'media.manage', 'storefront.manage', 'settings.seo', 'settings.appearance')
        ->and($presets['content']['permissions'])->not->toContain('pricing.manage', 'settings.mail')
        ->and($presets['analyst']['permissions'])->toContain('orders.view', 'orders.export', 'customers.view', 'inventory.view', 'promotion.view')
        ->and(collect($presets['analyst']['permissions'])->filter(fn (string $code): bool => ! str_ends_with($code, '.view') && ! in_array($code, ['admin.access', 'orders.export'], true))->all())->toBe([])
        ->and($presets['analyst']['permissions'])->not->toContain('customers.pii');

    // Phân quyền theo team ở mức module (Owner 2026-11-19): team không làm kho/giao hàng thì không có quyền nào của kho.
    foreach (['marketing', 'content'] as $team) {
        expect(collect($presets[$team]['permissions'])->filter(fn (string $code): bool => preg_match('/^(inventory|fulfillment|warehouse)\./', $code) === 1)->all())->toBe([], $team);
    }
});

it('quyền plugin khai mẫu tự vào đúng mẫu; trang sửa vai trò không có mẫu; lưu vai trò từ mẫu', function () {
    app(PermissionRegistry::class)->register('demo.posts', 'Bài viết demo', [PermissionRegistry::PRESET_CONTENT]);
    $presets = ($this->presets)();
    expect($presets['content']['permissions'])->toContain('demo.posts')->and($presets['marketing']['permissions'])->not->toContain('demo.posts');

    $this->post('/admin/staff/roles', ['code' => $presets['analyst']['code'], 'name' => $presets['analyst']['name'], 'permissions' => $presets['analyst']['permissions']])->assertSessionHasNoErrors();
    $role = Role::query()->where('code', 'analyst')->sole();
    expect($role->name)->toBe('Phân tích dữ liệu')->and($role->permissions()->pluck('permission')->sort()->values()->all())->toBe(collect($presets['analyst']['permissions'])->sort()->values()->all());
    $this->get("/admin/staff/roles/{$role->id}/edit")->assertInertia(fn (Assert $page) => $page->where('presets', []));
});
