<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class CategoryTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'category_translations';

    protected $fillable = ['category_id', 'locale', 'name', 'description', 'meta_title', 'meta_description'];
}
