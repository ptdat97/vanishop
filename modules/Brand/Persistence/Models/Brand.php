<?php

declare(strict_types=1);

namespace Modules\Brand\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Brand\Persistence\Database\Factories\BrandFactory;
use Modules\Tenancy\Persistence\Models\LegalEntity;

/**
 * @property int $id
 * @property int $legal_entity_id
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property string $status
 * @property array<string, mixed>|null $theme_tokens
 */
final class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = ['legal_entity_id', 'code', 'name', 'slug', 'status', 'theme_tokens'];

    protected function casts(): array
    {
        return ['theme_tokens' => 'array'];
    }

    /**
     * @return BelongsTo<LegalEntity, $this>
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }
}
