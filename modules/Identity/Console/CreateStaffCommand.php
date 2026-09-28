<?php

declare(strict_types=1);

namespace Modules\Identity\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * Tạo nhân viên quản trị cấp Owner đầu tiên.
 */
final class CreateStaffCommand extends Command
{
    protected $signature = 'vani:staff:create-owner {email} {name} {--password= : Mật khẩu (bỏ trống sẽ hỏi)}';

    protected $description = 'Tạo nhân viên có vai trò owner_admin (toàn quyền, phạm vi Owner)';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Mật khẩu');

        $validator = Validator::make(
            ['email' => $this->argument('email'), 'password' => $password],
            ['email' => ['required', 'email', 'unique:staff_users,email'], 'password' => ['required', 'string', Password::defaults()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($password): void {
            $role = Role::query()->firstOrCreate(['code' => 'owner_admin'], ['name' => 'Owner Admin', 'is_system' => true]);
            $role->syncPermissions([PermissionRegistry::WILDCARD]);

            $staff = StaffUser::query()->create([
                'name' => $this->argument('name'),
                'email' => $this->argument('email'),
                'password' => $password,
            ]);

            $staff->roleAssignments()->create(['role_id' => $role->id, 'scope_type' => ScopeType::Owner, 'scope_id' => null]);
        });

        $this->info('Đã tạo nhân viên '.$this->argument('email').' với vai trò owner_admin.');

        return self::SUCCESS;
    }
}
