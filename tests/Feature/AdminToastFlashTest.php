<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Persistence\Models\StaffUser;

/*
| Thông báo Admin qua flash data của Inertia (`toasts`): mỗi phản hồi mang flash phát sự kiện `flash` ở client → mỗi thao tác một
| toast; không còn prop chia sẻ `flash` (lưu vào lịch sử trình duyệt, theo dõi theo object nên lặp nội dung không hiện lại).
*/

beforeEach(function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'catalog.view', 'catalog.manage'])->create(), 'staff');
});

it('mỗi thao tác một thông báo: redirect sau thao tác mang flash toasts (tone success), lần tải sau không còn; không còn prop flash', function () {
    $create = fn (string $code) => $this->post('/admin/catalog/option-types', ['code' => $code, 'display_type' => 'button', 'position' => 0, 'translations' => ['vi' => ['name' => 'Chất liệu']]])
        ->assertRedirect();

    foreach (['chat_lieu', 'dung_tich'] as $code) {
        // Hai thao tác cùng nội dung "Đã lưu." → mỗi lần đều có flash (client phát sự kiện flash ở mỗi phản hồi).
        $create($code);
        $this->get('/admin/catalog/option-types')->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts.0', ['type' => 'success', 'message' => __('catalog::messages.saved')])
            ->missing('flash'));
    }
    $this->get('/admin/catalog/option-types')->assertInertia(fn (Assert $page) => $page->missingFlash('toasts'));
});
