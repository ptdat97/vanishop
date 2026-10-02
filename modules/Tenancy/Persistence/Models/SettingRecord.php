<?php

declare(strict_types=1);

namespace Modules\Tenancy\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $namespace
 * @property string $key
 * @property string $value
 * @property bool $encrypted
 */
final class SettingRecord extends Model
{
    protected $table = 'settings';

    protected $fillable = ['namespace', 'key', 'value', 'encrypted'];

    protected function casts(): array
    {
        return ['encrypted' => 'boolean'];
    }
}
