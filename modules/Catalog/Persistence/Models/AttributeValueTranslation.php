<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class AttributeValueTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'attribute_value_translations';

    protected $fillable = ['attribute_value_id', 'locale', 'label'];
}
