<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Persistence\Database\Factories\ColorFactory;
use Modules\Shared\Persistence\Concerns\BelongsToBrand;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * @property int $id
 * @property int $brand_id
 * @property string $code
 * @property ColorFamily $color_family
 * @property string|null $hex
 * @property int $position
 */
final class Color extends Model
{
    use BelongsToBrand;

    /** @use HasFactory<ColorFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = ['brand_id', 'code', 'color_family', 'hex', 'position'];

    protected function casts(): array
    {
        return ['color_family' => ColorFamily::class, 'position' => 'integer'];
    }

    protected function translationModel(): string
    {
        return ColorTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }

    protected static function newFactory(): ColorFactory
    {
        return ColorFactory::new();
    }
}
