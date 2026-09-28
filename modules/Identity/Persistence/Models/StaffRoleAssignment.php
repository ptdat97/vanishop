<?php

declare(strict_types=1);

namespace Modules\Identity\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\ScopeType;

/**
 * @property int $id
 * @property int $staff_user_id
 * @property int $role_id
 * @property ScopeType $scope_type
 * @property int|null $scope_id
 * @property Role $role
 */
final class StaffRoleAssignment extends Model
{
    protected $fillable = ['staff_user_id', 'role_id', 'scope_type', 'scope_id'];

    protected function casts(): array
    {
        return [
            'scope_type' => ScopeType::class,
            'scope_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
