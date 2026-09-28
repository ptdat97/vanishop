<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class ProductCollectionTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'collection_translations';

    protected $fillable = ['collection_id', 'locale', 'name', 'description'];
}
