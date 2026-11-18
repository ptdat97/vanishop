<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Persistence\Models\StaffUser;

/*
| Sidebar Admin dạng accordion (Core 0.3.38): mục thuộc nhóm; chỉ gửi nhóm có mục nhân viên xem được; nhóm lạ → "Mở rộng".
*/

it('mục có nhóm; chỉ nhóm có mục được phép mới gửi xuống; mục không nhóm ở cấp trên cùng', function () {
    $staff = StaffUser::factory()->withPermissions([
        'admin.access',
        'orders.view',
        'returns.view',
        'customers.view',
        'payments.view',
        'catalog.view',
        'media.view',
        'extension.plugins.view',
    ])->create();

    $this->actingAs($staff, 'staff')->get('/admin')->assertInertia(fn (Assert $page) => $page
        ->where('navigation', fn ($items) => collect($items)->pluck('group', 'key')->only(['dashboard', 'orders', 'returns', 'customers', 'payment', 'catalog', 'media', 'plugins'])->sortKeys()->all()
            // Chỉ kiểm nhóm của từng mục; thứ tự mục trong menu do vị trí (position) quyết, không thuộc test này.
            === ['catalog' => 'catalog', 'customers' => null, 'dashboard' => null, 'media' => 'content', 'orders' => 'sales', 'payment' => 'sales', 'plugins' => null, 'returns' => 'sales'])
        ->where('navigationGroups', fn ($groups) => collect($groups)->pluck('key')->all() === ['sales', 'catalog', 'content'])
        ->where('navigationGroups.0.label', 'Bán hàng'));
});

it('mục khai báo nhóm chưa đăng ký rơi vào nhóm "Mở rộng"', function () {
    app(AdminNavigation::class)->add('test-x', 'Mục thử', 'admin.dashboard', 'admin.access', 860, group: 'khong-co');
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->get('/admin')->assertInertia(fn (Assert $page) => $page
        ->where('navigation', fn ($items) => collect($items)->firstWhere('key', 'test-x')['group'] === AdminNavigation::FALLBACK_GROUP)
        ->where('navigationGroups', fn ($groups) => collect($groups)->pluck('label')->all() === ['Mở rộng']));
});
