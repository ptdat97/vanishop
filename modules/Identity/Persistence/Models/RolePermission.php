<?php

declare(strict_types=1);

namespace Modules\Identity\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $role_id
 * @property string $permission
 */
final class RolePermission extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['role_id', 'permission'];
}
