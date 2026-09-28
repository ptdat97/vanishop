<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class AttributeTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'attribute_translations';

    protected $fillable = ['attribute_id', 'locale', 'name'];
}
