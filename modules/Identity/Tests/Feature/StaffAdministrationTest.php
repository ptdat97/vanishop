<?php

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Inventory\Persistence\Models\Location;

/*
| reference-parity §3.3-1/2/4 (R1): Admin → Nhân viên, Vai trò, quên mật khẩu Admin.
*/

/** Link trong email gửi đi (lấy từ nội dung mail đã chặn). */
function sentLinks(): array
{
    return array_map(fn (MessageSending $event): string => (string) preg_replace('/.*?(https?:\/\/\S+).*/s', '$1', $event->message->getTextBody()), Event::dispatched(MessageSending::class)->map(fn (array $args) => $args[0])->all());
}

function ownerRole(): Role
{
    $role = Role::query()->firstOrCreate(['code' => 'owner_admin'], ['name' => 'Owner Admin', 'is_system' => true]);
    $role->syncPermissions([PermissionRegistry::WILDCARD]);

    return $role;
}

function makeOwner(array $attributes = []): StaffUser
{
    $staff = StaffUser::factory()->create($attributes);
    $staff->roleAssignments()->create(['role_id' => ownerRole()->id, 'scope_type' => ScopeType::Owner, 'scope_id' => null]);

    return $staff;
}

beforeEach(function () {
    Event::fake([MessageSending::class]);
    $this->owner = makeOwner(['name' => 'Chủ shop']);
    $this->clerk = Role::query()->create(['code' => 'ban_hang', 'name' => 'Bán hàng']);
    $this->clerk->syncPermissions(['admin.access', 'orders.view']);
});

describe('nhân viên', function () {
    it('danh sách: tìm theo tên/email, lọc vai trò và trạng thái; cần quyền staff.manage', function () {
        StaffUser::factory()->create(['name' => 'Lan Bán Hàng', 'status' => 'locked'])->roleAssignments()->create(['role_id' => $this->clerk->id, 'scope_type' => ScopeType::Owner]);

        $this->actingAs($this->owner, 'staff')->get('/admin/staff?q=lan&status=locked&role='.$this->clerk->id.'&per_page=50')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Identity::Staff/Index')->where('staff.total', 1)->where('staff.data.0.roles', ['Bán hàng'])->where('staff.per_page', 50));
        $this->get('/admin/staff?per_page=101')->assertSessionHasErrors('per_page');

        $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff')->get('/admin/staff')->assertForbidden();
    });

    it('mời: tạo ở trạng thái invited, gửi link 24 giờ; nhận lời mời → đặt mật khẩu, active, đăng nhập được', function () {
        $this->actingAs($this->owner, 'staff')->post('/admin/staff', [
            'name' => 'Minh Kho', 'email' => 'minh@example.com',
            'assignments' => [['role_id' => $this->clerk->id, 'scope_type' => 'owner', 'scope_id' => null]],
        ])->assertRedirect()->assertSessionHas('success');

        $staff = StaffUser::query()->where('email', 'minh@example.com')->sole();
        expect($staff->status)->toBe('invited')
            ->and($staff->roleAssignments()->count())->toBe(1)
            ->and(AuditLog::query()->where('action', 'identity.staff.invited')->where('subject_id', $staff->id)->exists())->toBeTrue();

        $link = sentLinks()[0];
        expect($link)->toContain('/admin/loi-moi/');
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $token = basename((string) parse_url($link, PHP_URL_PATH));

        auth('staff')->logout();
        $this->post('/admin/login', ['email' => 'minh@example.com', 'password' => 'mat-khau-moi-123'])->assertSessionHasErrors('email');

        // Hết 24 giờ → link không còn hiệu lực.
        $this->travel(25)->hours();
        $this->post('/admin/loi-moi', ['token' => $token, 'email' => $query['email'], 'password' => 'mat-khau-moi-123', 'password_confirmation' => 'mat-khau-moi-123'])->assertSessionHasErrors('email');
        $this->travelBack();

        $this->get($link)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Identity::Auth/SetPassword')->where('email', 'minh@example.com'));
        $this->post('/admin/loi-moi', ['token' => $token, 'email' => $query['email'], 'password' => 'mat-khau-moi-123', 'password_confirmation' => 'mat-khau-moi-123'])
            ->assertRedirect(route('admin.login'));
        expect($staff->refresh()->status)->toBe('active');

        // Link chỉ dùng một lần.
        $this->post('/admin/loi-moi', ['token' => $token, 'email' => $query['email'], 'password' => 'mat-khau-khac-456', 'password_confirmation' => 'mat-khau-khac-456'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => 'minh@example.com', 'password' => 'mat-khau-moi-123'])->assertRedirect(route('admin.dashboard'));
    });

    it('gán vai trò theo một location; location không tồn tại bị từ chối', function () {
        $location = Location::query()->create(['code' => 'ST-Q1', 'name' => 'Quận 1', 'type' => 'store']);

        $this->actingAs($this->owner, 'staff')->get('/admin/staff/create')
            ->assertInertia(fn (Assert $page) => $page->where("scopes.location.{$location->id}", 'ST-Q1 — Quận 1'));
        $this->post('/admin/staff', ['name' => 'A', 'email' => 'a@example.com', 'assignments' => [['role_id' => $this->clerk->id, 'scope_type' => 'location', 'scope_id' => 999]]])
            ->assertSessionHasErrors('assignments.0.scope_id');
        $this->post('/admin/staff', ['name' => 'A', 'email' => 'a@example.com', 'assignments' => [['role_id' => $this->clerk->id, 'scope_type' => 'location', 'scope_id' => $location->id]]])
            ->assertSessionHasNoErrors();

        expect(StaffUser::query()->where('email', 'a@example.com')->sole()->roleAssignments()->sole()->scope_id)->toBe($location->id);
    });

    it('khoá: bị đăng xuất, không đăng nhập, không nhận link đặt lại; mở khoá được; có audit', function () {
        $staff = StaffUser::factory()->withPermissions(['admin.access'])->create(['password' => 'mat-khau-cu-12345', 'last_login_at' => now()]);

        $this->actingAs($this->owner, 'staff')->post("/admin/staff/{$staff->id}/lock")->assertSessionHas('success');
        expect($staff->refresh()->status)->toBe('locked');

        $this->actingAs($staff, 'staff')->get('/admin')->assertRedirect(route('admin.login'));
        $this->post('/admin/login', ['email' => $staff->email, 'password' => 'mat-khau-cu-12345'])->assertSessionHasErrors('email');
        $this->post('/admin/quen-mat-khau', ['email' => $staff->email])->assertSessionHas('success');
        expect(sentLinks())->toBe([]);

        $this->actingAs($this->owner, 'staff')->post("/admin/staff/{$staff->id}/unlock");
        expect($staff->refresh()->status)->toBe('active')
            ->and(AuditLog::query()->whereIn('action', ['identity.staff.locked', 'identity.staff.unlocked'])->count())->toBe(2);
    });

    it('không tự khoá, không tự đổi vai trò; vẫn tự đổi tên được', function () {
        $this->actingAs($this->owner, 'staff')->post("/admin/staff/{$this->owner->id}/lock")->assertSessionHasErrors('business');
        $this->put("/admin/staff/{$this->owner->id}", ['name' => 'Chủ', 'assignments' => [['role_id' => $this->clerk->id, 'scope_type' => 'owner']]])->assertSessionHasErrors('business');
        $this->put("/admin/staff/{$this->owner->id}", ['name' => 'Chủ cửa hàng', 'assignments' => [['role_id' => ownerRole()->id, 'scope_type' => 'owner']]])->assertSessionHasNoErrors();

        expect($this->owner->refresh()->status)->toBe('active')->and($this->owner->name)->toBe('Chủ cửa hàng');
    });

    it('không khoá hoặc hạ quyền Owner cuối cùng (người thao tác có staff.manage nhưng không phải Owner)', function () {
        $manager = StaffUser::factory()->withPermissions(['admin.access', 'staff.manage', 'orders.view'])->create();

        $this->actingAs($manager, 'staff')->post("/admin/staff/{$this->owner->id}/lock")->assertSessionHasErrors(['business' => 'Phải còn ít nhất một nhân viên Owner đang hoạt động.']);
        $this->put("/admin/staff/{$this->owner->id}", ['name' => 'Chủ shop', 'assignments' => [['role_id' => $this->clerk->id, 'scope_type' => 'owner']]])->assertSessionHasErrors('business');
        expect($this->owner->refresh()->status)->toBe('active');

        // Có Owner thứ hai → khoá được.
        makeOwner();
        $this->actingAs($this->owner, 'staff')->post("/admin/staff/{$manager->id}/lock")->assertSessionHasNoErrors();
    });

    it('không cấp vai trò có quyền mình không có (vai trò `*` chỉ Owner cấp được)', function () {
        $manager = StaffUser::factory()->withPermissions(['admin.access', 'staff.manage'])->create();

        $this->actingAs($manager, 'staff')->post('/admin/staff', ['name' => 'X', 'email' => 'x@example.com', 'assignments' => [['role_id' => ownerRole()->id, 'scope_type' => 'owner']]])
            ->assertSessionHasErrors(['business' => 'Không thể cấp quyền mà bạn không có.']);
        $this->post('/admin/staff', ['name' => 'X', 'email' => 'x@example.com', 'assignments' => [['role_id' => $this->clerk->id, 'scope_type' => 'owner']]])
            ->assertSessionHasErrors('business'); // thiếu orders.view
        expect(StaffUser::query()->where('email', 'x@example.com')->exists())->toBeFalse();
    });
});

describe('vai trò', function () {
    it('tạo vai trò với quyền plugin; sửa chỉ thêm được quyền mình có; không cấp `*`; có audit', function () {
        app(PermissionRegistry::class)->register('plugin.demo', 'Quyền của plugin');

        $this->actingAs($this->owner, 'staff')->get('/admin/staff/roles/create')
            ->assertInertia(fn (Assert $page) => $page->component('Identity::Roles/Form')->where('groups', fn ($groups) => collect($groups)->contains('key', 'plugin')));
        $this->post('/admin/staff/roles', ['code' => 'kho', 'name' => 'Kho', 'permissions' => ['*']])->assertSessionHasErrors('permissions.0');
        $this->post('/admin/staff/roles', ['code' => 'kho', 'name' => 'Kho', 'permissions' => ['admin.access', 'plugin.demo']])->assertSessionHasNoErrors();
        $role = Role::query()->where('code', 'kho')->sole();

        $manager = StaffUser::factory()->withPermissions(['admin.access', 'staff.manage'])->create();
        $this->actingAs($manager, 'staff')->put("/admin/staff/roles/{$role->id}", ['name' => 'Kho', 'permissions' => ['admin.access', 'plugin.demo', 'orders.view']])
            ->assertSessionHasErrors('business');
        $this->put("/admin/staff/roles/{$role->id}", ['name' => 'Kho tổng', 'permissions' => ['admin.access', 'plugin.demo']])->assertSessionHasNoErrors();

        expect($role->refresh()->name)->toBe('Kho tổng')
            ->and(AuditLog::query()->whereIn('action', ['identity.role.created', 'identity.role.updated'])->count())->toBe(2);
    });

    it('vai trò hệ thống không sửa/xoá được; vai trò đang gán không xoá được', function () {
        $system = ownerRole();
        StaffUser::factory()->create()->roleAssignments()->create(['role_id' => $this->clerk->id, 'scope_type' => ScopeType::Owner]);
        $unused = Role::query()->create(['code' => 'tam', 'name' => 'Tạm']);

        $this->actingAs($this->owner, 'staff')->put("/admin/staff/roles/{$system->id}", ['name' => 'X', 'permissions' => []])->assertSessionHasErrors('business');
        $this->delete("/admin/staff/roles/{$system->id}")->assertSessionHasErrors('business');
        $this->delete("/admin/staff/roles/{$this->clerk->id}")->assertSessionHasErrors(['business' => 'Vai trò đang được gán cho nhân viên, không xoá được.']);
        $this->delete("/admin/staff/roles/{$unused->id}")->assertRedirect(route('admin.staff.roles.index'));

        expect(Role::query()->whereKey($unused->id)->exists())->toBeFalse()
            ->and($system->refresh()->permissions()->pluck('permission')->all())->toBe(['*']);
    });
});

describe('quên mật khẩu Admin', function () {
    it('gửi link một lần; không tiết lộ email; đặt lại xong đăng nhập bằng mật khẩu mới; có audit', function () {
        $this->post('/admin/quen-mat-khau', ['email' => 'khong-ton-tai@example.com'])->assertSessionHas('success', 'Nếu email thuộc một nhân viên đang hoạt động, link đặt lại mật khẩu đã được gửi.');
        expect(sentLinks())->toBe([]);

        $this->post('/admin/quen-mat-khau', ['email' => $this->owner->email])->assertSessionHas('success', 'Nếu email thuộc một nhân viên đang hoạt động, link đặt lại mật khẩu đã được gửi.');
        $link = sentLinks()[0];
        expect($link)->toContain('/admin/dat-lai-mat-khau/')->toStartWith(config('app.url'));
        $token = basename((string) parse_url($link, PHP_URL_PATH));

        $this->post('/admin/dat-lai-mat-khau', ['token' => $token, 'email' => $this->owner->email, 'password' => 'ngan', 'password_confirmation' => 'ngan'])->assertSessionHasErrors('password');
        $this->post('/admin/dat-lai-mat-khau', ['token' => 'sai', 'email' => $this->owner->email, 'password' => 'mat-khau-moi-123', 'password_confirmation' => 'mat-khau-moi-123'])->assertSessionHasErrors('email');
        $this->post('/admin/dat-lai-mat-khau', ['token' => $token, 'email' => $this->owner->email, 'password' => 'mat-khau-moi-123', 'password_confirmation' => 'mat-khau-moi-123'])
            ->assertRedirect(route('admin.login'));

        $this->post('/admin/login', ['email' => $this->owner->email, 'password' => 'mat-khau-moi-123'])->assertRedirect(route('admin.dashboard'));
        expect(AuditLog::query()->whereIn('action', ['identity.staff.password_reset_requested', 'identity.staff.password_reset'])->count())->toBe(2);
    });

    it('link hết hạn sau 60 phút', function () {
        $this->post('/admin/quen-mat-khau', ['email' => $this->owner->email]);
        $token = basename((string) parse_url(sentLinks()[0], PHP_URL_PATH));

        $this->travel(61)->minutes();
        $this->post('/admin/dat-lai-mat-khau', ['token' => $token, 'email' => $this->owner->email, 'password' => 'mat-khau-moi-123', 'password_confirmation' => 'mat-khau-moi-123'])
            ->assertSessionHasErrors(['email' => 'Link không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu link mới.']);
    });

    it('giới hạn theo email + IP (chung một thông báo cho email có và không có)', function () {
        foreach (range(1, 3) as $attempt) {
            $this->post('/admin/quen-mat-khau', ['email' => 'ai-do@example.com'])->assertSessionHasNoErrors();
        }

        $this->post('/admin/quen-mat-khau', ['email' => 'ai-do@example.com'])->assertSessionHasErrors('email');
        $this->post('/admin/quen-mat-khau', ['email' => $this->owner->email])->assertSessionHasNoErrors();
    });
});
