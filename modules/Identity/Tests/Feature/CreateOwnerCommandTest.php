<?php

use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Persistence\Models\StaffUser;

it('tạo nhân viên owner_admin toàn quyền', function () {
    $this->artisan('vani:staff:create-owner', ['email' => 'owner@vani.test', 'name' => 'Owner', '--password' => 'a-very-long-password'])
        ->assertSuccessful();

    $staff = StaffUser::query()->where('email', 'owner@vani.test')->sole();
    expect(app(Authorizer::class)->allows($staff->id, 'anything.at.all'))->toBeTrue();
});

it('từ chối mật khẩu ngắn và email trùng', function () {
    StaffUser::factory()->create(['email' => 'taken@vani.test']);

    $this->artisan('vani:staff:create-owner', ['email' => 'new@vani.test', 'name' => 'X', '--password' => 'short'])->assertFailed();
    $this->artisan('vani:staff:create-owner', ['email' => 'taken@vani.test', 'name' => 'X', '--password' => 'a-very-long-password'])->assertFailed();
});
