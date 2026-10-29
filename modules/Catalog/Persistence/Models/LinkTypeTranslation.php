<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class LinkTypeTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'link_type_translations';

    protected $fillable = ['link_type_id', 'locale', 'name'];
}
