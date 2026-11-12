<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class OptionValueTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['option_value_id', 'locale', 'name'];
}
