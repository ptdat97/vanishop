<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class OptionTypeTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['option_type_id', 'locale', 'name'];
}
