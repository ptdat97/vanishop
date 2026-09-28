<?php

declare(strict_types=1);

namespace Modules\Identity\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
final class Role extends Model
{
    protected $fillable = ['code', 'name', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /**
     * @return HasMany<RolePermission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions()->delete();
        $this->permissions()->createMany(array_map(fn (string $code): array => ['permission' => $code], array_values(array_unique($permissions))));
        $this->unsetRelation('permissions');
    }
}
