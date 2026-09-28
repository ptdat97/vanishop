<?php

declare(strict_types=1);

namespace Modules\Identity\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Identity\Persistence\Database\Factories\StaffUserFactory;

/**
 * Nhân viên đăng nhập Admin (guard "staff"), tách khỏi khách hàng.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $status
 */
final class StaffUser extends Authenticatable
{
    /** @use HasFactory<StaffUserFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'password', 'status', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<StaffRoleAssignment, $this>
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(StaffRoleAssignment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    protected static function newFactory(): StaffUserFactory
    {
        return StaffUserFactory::new();
    }
}
