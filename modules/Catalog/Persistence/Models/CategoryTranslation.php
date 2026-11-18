<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Persistence\Concerns\RecordsTranslatedSlugRedirects;

final class CategoryTranslation extends Model
{
    use RecordsTranslatedSlugRedirects;

    /** Slug riêng đổi → link cũ của ngôn ngữ chuyển hướng 301 (L44). */
    public const SLUG_SUBJECT = CatalogReader::SLUG_CATEGORY;

    public const SLUG_OWNER_KEY = 'category_id';

    public $timestamps = false;

    protected $table = 'category_translations';

    protected $fillable = ['category_id', 'locale', 'slug', 'name', 'description', 'meta_title', 'meta_description'];
}
