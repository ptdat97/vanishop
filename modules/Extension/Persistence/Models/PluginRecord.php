<?php

declare(strict_types=1);

namespace Modules\Extension\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Extension\Domain\Plugin\PluginStatus;

/**
 * @property string $id
 * @property string $version
 * @property PluginStatus $status
 * @property string|null $last_error
 */
final class PluginRecord extends Model
{
    protected $table = 'plugins';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'version', 'status', 'last_error', 'installed_at'];

    protected function casts(): array
    {
        return [
            'status' => PluginStatus::class,
            'installed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PluginScopeRecord, $this>
     */
    public function scopes(): HasMany
    {
        return $this->hasMany(PluginScopeRecord::class, 'plugin_id');
    }
}
