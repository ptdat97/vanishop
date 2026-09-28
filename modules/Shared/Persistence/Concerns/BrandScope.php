<?php

declare(strict_types=1);

namespace Modules\Shared\Persistence\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Shared\Context\CurrentContext;

/**
 * Lọc bản ghi theo các brand mà CurrentContext cho phép. Thiếu context → MissingContext.
 */
final class BrandScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $brandIds = app(CurrentContext::class)->brandIds();

        if ($brandIds !== null) {
            $builder->whereIn($model->qualifyColumn('brand_id'), $brandIds);
        }
    }
}
