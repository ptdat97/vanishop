<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class StyleTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['style_id', 'locale', 'name', 'description', 'care_instructions', 'meta_title', 'meta_description'];
}
