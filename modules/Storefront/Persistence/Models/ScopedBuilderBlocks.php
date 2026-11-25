<?php

declare(strict_types=1);

namespace Modules\Storefront\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Block Builder của một phạm vi (ScopedBuilderSurface).
 *
 * @property int $id
 * @property string $surface
 * @property string $scope
 * @property list<array{type: string, config: array<string, mixed>, zone?: string}> $blocks
 */
final class ScopedBuilderBlocks extends Model
{
    protected $table = 'builder_scoped_blocks';

    protected $fillable = ['surface', 'scope', 'blocks'];

    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }
}
