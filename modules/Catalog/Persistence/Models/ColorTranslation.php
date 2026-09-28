<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class ColorTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'color_translations';

    protected $fillable = ['color_id', 'locale', 'name'];
}
