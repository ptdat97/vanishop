<?php

declare(strict_types=1);

namespace Modules\Extension\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $plugin_id
 * @property string $scope_type owner|legal_entity|brand|channel
 * @property int|null $scope_id
 * @property bool $enabled
 */
final class PluginScopeRecord extends Model
{
    protected $table = 'plugin_scopes';

    protected $fillable = ['plugin_id', 'scope_type', 'scope_id', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'scope_id' => 'integer'];
    }
}
