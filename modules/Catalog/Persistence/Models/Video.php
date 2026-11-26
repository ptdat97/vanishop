<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Video ở nền tảng ngoài; `driver` là mã VideoDriver (plugin), `reference` là định danh video trên nền tảng đó.
 *
 * @property int $id
 * @property string $driver
 * @property string $reference
 * @property string|null $title
 * @property array<string, mixed>|null $data
 */
final class Video extends Model
{
    protected $fillable = ['driver', 'reference', 'title', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('position');
    }
}
