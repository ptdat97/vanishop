<?php

declare(strict_types=1);

namespace Modules\Identity\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $staff_user_id
 * @property int $role_id
 * @property Role $role
 */
final class StaffRoleAssignment extends Model
{
    protected $fillable = ['staff_user_id', 'role_id'];

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
