<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Persistence\Concerns\RecordsTranslatedSlugRedirects;

final class ProductTranslation extends Model
{
    use RecordsTranslatedSlugRedirects;

    /** Slug riêng đổi → link cũ của ngôn ngữ chuyển hướng 301 (L44). */
    public const SLUG_SUBJECT = CatalogReader::SLUG_PRODUCT;

    public const SLUG_OWNER_KEY = 'product_id';

    public $timestamps = false;

    protected $fillable = ['product_id', 'locale', 'slug', 'name', 'description', 'care_instructions', 'meta_title', 'meta_description'];
}
