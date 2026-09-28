<?php

declare(strict_types=1);

namespace Modules\Shared\Persistence\Concerns;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Context\CurrentContext;

/**
 * Dùng cho model có cột brand_id: lọc đọc theo phạm vi và chặn ghi ngoài phạm vi.
 *
 * @mixin Model
 */
trait BelongsToBrand
{
    public static function bootBelongsToBrand(): void
    {
        static::addGlobalScope(new BrandScope);

        static::saving(function (Model $model): void {
            $brandId = $model->getAttribute('brand_id');

            if ($brandId === null) {
                throw new BrandAccessDenied(null);
            }

            if (! app(CurrentContext::class)->scope()->allowsBrand((int) $brandId)) {
                throw new BrandAccessDenied((int) $brandId);
            }
        });

        static::deleting(function (Model $model): void {
            if (! app(CurrentContext::class)->scope()->allowsBrand((int) $model->getAttribute('brand_id'))) {
                throw new BrandAccessDenied((int) $model->getAttribute('brand_id'));
            }
        });
    }
}
