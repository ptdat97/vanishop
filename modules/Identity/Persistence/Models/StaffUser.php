<?php

declare(strict_types=1);

namespace Modules\Identity\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Modules\Identity\Persistence\Database\Factories\StaffUserFactory;

/**
 * Nhân viên đăng nhập Admin (guard "staff"), tách khỏi khách hàng.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $status
 * @property string|null $locale ngôn ngữ Admin riêng (null = mặc định của cửa hàng)
 * @property Carbon|null $last_login_at
 */
final class StaffUser extends Authenticatable
{
    /** @use HasFactory<StaffUserFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    /** Đã mời, chưa đặt mật khẩu qua link — không đăng nhập được. */
    public const STATUS_INVITED = 'invited';

    /** Bị khoá bởi quản trị — không đăng nhập, không nhận link đặt lại mật khẩu. Không xoá cứng nhân viên (audit). */
    public const STATUS_LOCKED = 'locked';

    protected $fillable = ['name', 'email', 'password', 'status', 'locale', 'last_login_at'];

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
        return $this->status === self::STATUS_ACTIVE;
    }

    protected static function newFactory(): StaffUserFactory
    {
        return StaffUserFactory::new();
    }
}
