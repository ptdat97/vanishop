<?php

declare(strict_types=1);

namespace Modules\Identity\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * @extends Factory<StaffUser>
 */
final class StaffUserFactory extends Factory
{
    protected $model = StaffUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'status' => 'active',
        ];
    }

    public function inactive(): self
    {
        return $this->state(['status' => 'inactive']);
    }

    /**
     * Gán vai trò có các permission cho trước trong một phạm vi.
     *
     * @param  list<string>  $permissions
     */
    public function withPermissions(array $permissions, ScopeType $scope = ScopeType::Owner, ?int $scopeId = null): self
    {
        return $this->afterCreating(function (StaffUser $staff) use ($permissions, $scope, $scopeId): void {
            $role = Role::query()->create(['code' => 'test-'.fake()->unique()->numberBetween(1, 1_000_000), 'name' => 'Test role']);
            $role->syncPermissions($permissions);
            $staff->roleAssignments()->create(['role_id' => $role->id, 'scope_type' => $scope, 'scope_id' => $scopeId]);
        });
    }
}
