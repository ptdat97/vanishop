<?php

declare(strict_types=1);

namespace Plugin\CatalogPages\Infrastructure;

use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Storefront\Contracts\Data\BlockContext;
use Modules\Storefront\Contracts\Data\BuilderScope;
use Modules\Storefront\Contracts\Data\BuilderZone;
use Modules\Storefront\Contracts\ScopedBuilderSurface;

/**
 * Một surface cho cả ba loại trang danh sách; phạm vi là `<loại>-<id>` (vd. `brand-3`). Vùng gắn vào slot có sẵn của
 * Core nên theme không cần sửa.
 */
final class CatalogPageSurface implements ScopedBuilderSurface
{
    public const KEY = 'catalog-pages';

    public const KINDS = ['category', 'brand', 'collection'];

    /** Route sửa đối tượng trong Admin theo loại. */
    private const ADMIN_ROUTES = ['category' => 'admin.catalog.categories.edit', 'brand' => 'admin.catalog.brands.edit', 'collection' => 'admin.catalog.collections.edit'];

    public function __construct(private readonly CatalogLookup $lookup) {}

    /**
     * @param  'category'|'brand'|'collection'  $kind
     */
    public static function scope(string $kind, int $id): string
    {
        return "{$kind}-{$id}";
    }

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return __('vani-catalog-pages::texts.surface');
    }

    public function zones(): array
    {
        return [
            BuilderZone::slot('vani.storefront.plp.header', __('vani-catalog-pages::texts.zone_header'), max: 2),
            BuilderZone::slot('vani.storefront.plp.before_listing', __('vani-catalog-pages::texts.zone_before_listing')),
            BuilderZone::slot('vani.storefront.plp.after_listing', __('vani-catalog-pages::texts.zone_after_listing')),
            BuilderZone::slot('vani.storefront.plp.empty', __('vani-catalog-pages::texts.zone_empty')),
        ];
    }

    public function scopeOf(BlockContext $context): ?string
    {
        return in_array($context->listingType, self::KINDS, true) && $context->listingId !== null
            ? self::scope($context->listingType, $context->listingId)
            : null;
    }

    public function describeScope(string $scope): ?BuilderScope
    {
        if (preg_match('/^(category|brand|collection)-([1-9]\d{0,18})$/', $scope, $match) !== 1) {
            return null;
        }
        [, $kind, $id] = $match;
        $item = $this->lookup->adminItems($kind, [(int) $id], App::getLocale())[(int) $id] ?? null;
        if ($item === null) {
            return null;
        }

        return new BuilderScope(
            label: __("vani-catalog-pages::texts.scope_{$kind}", ['name' => $item['name']]),
            previewUrl: $item['visible'] ? vani_route("storefront.{$kind}", [$item['slug']]) : null,
            backUrl: route(self::ADMIN_ROUTES[$kind], (int) $id),
            listingType: $kind,
            listingSlug: $item['slug'],
        );
    }
}
