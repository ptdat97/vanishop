<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Domain\SizeSystem;
use Modules\Catalog\Persistence\Database\Factories\SizeFactory;

/**
 * @property int $id
 * @property SizeSystem $size_system
 * @property string $code
 * @property int $sort_order
 */
final class Size extends Model
{
    /** @use HasFactory<SizeFactory> */
    use HasFactory;

    protected $fillable = ['size_system', 'code', 'sort_order'];

    protected function casts(): array
    {
        return ['size_system' => SizeSystem::class, 'sort_order' => 'integer'];
    }

    protected static function newFactory(): SizeFactory
    {
        return SizeFactory::new();
    }
}
