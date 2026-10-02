<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * Bộ sưu tập thủ công (landing page, campaign). Bộ sưu tập theo luật: Designed.
 *
 * @property int $id
 * @property string $slug
 * @property string $status
 * @property int $position
 */
final class ProductCollection extends Model
{
    use HasTranslations;

    protected $table = 'collections';

    protected $fillable = ['slug', 'status', 'position'];

    protected function translationModel(): string
    {
        return ProductCollectionTranslation::class;
    }

    protected function translationForeignKey(): ?string
    {
        return 'collection_id';
    }

    public function translatableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * @return BelongsToMany<Style, $this>
     */
    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class, 'collection_style', 'collection_id', 'style_id')->withPivot('position')->orderByPivot('position');
    }
}
