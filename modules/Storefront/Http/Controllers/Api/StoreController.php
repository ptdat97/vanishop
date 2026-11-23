<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use JsonSerializable;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Storefront\Application\Menus\MenuPresenter;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Application\StoreProfile;
use Modules\Storefront\Application\Theme\Themes;

/**
 * "Vỏ" trang cho client không phải Blade (ADR-044): thông tin cửa hàng, menu theo vị trí, trang
 * chủ dựng bằng khối. Cùng use-case với layout và trang chủ của Blade.
 */
final class StoreController
{
    public const PUBLIC_LAYOUTS = ['listing', 'product', 'brands', 'cart'];

    public function show(StoreProfile $profile): JsonResponse
    {
        return response()->json(['data' => $profile->present(App::getLocale())]);
    }

    /**
     * Menu đã xuất bản của một vị trí theme khai báo (`header`, `mobile`, `footer_1`…). Chưa xuất bản → `data: []`,
     * `meta.published: false` (client dùng menu mặc định của mình); vị trí lạ → 404.
     */
    public function menu(string $location, MenuPresenter $menus, Themes $themes): JsonResponse
    {
        abort_unless(array_key_exists($location, $themes->menuLocations()), 404, __('storefront::messages.menu_location_unknown'));
        $items = $menus->items($location, App::getLocale());

        return response()->json(['data' => $items ?? [], 'meta' => ['location' => $location, 'published' => $items !== null]]);
    }

    /**
     * Trang chủ dựng bằng khối (Admin → Giao diện → Trang chủ): `[{type, data}]` — `data` là dữ liệu khối đã dựng, giống
     * dữ liệu view của khối trên Blade (HTML đã làm sạch ở dạng chuỗi, vd. `bodyHtml`). Chưa cấu hình → `data: null`,
     * `meta.configured: false` (client dùng trang chủ mặc định của mình).
     */
    public function home(BuilderEngine $builder, PageBlocks $blocks): JsonResponse
    {
        $configured = $builder->zone(BuilderEngine::HOME, 'main');

        return response()->json([
            'data' => $configured === null ? null : array_map(fn (array $block): array => ['type' => $block['type'], 'data' => self::json($block['data'])], $blocks->resolve($configured)),
            'meta' => ['configured' => $configured !== null],
        ]);
    }

    public function layout(string $surface, Request $request, BuilderEngine $builder, CatalogReader $catalog, CollectionPages $collections): JsonResponse
    {
        abort_if($builder->surface($surface) === null, 404);
        $listing = $surface === 'listing';
        $usesSlug = $surface === 'product' || ($listing && $request->query('type') !== 'search');
        $context = $request->validate([
            'type' => [$listing ? 'required' : 'prohibited', 'string', Rule::in(['category', 'collection', 'brand', 'search'])],
            'slug' => [$usesSlug ? 'required' : 'prohibited', 'string', 'max:255'],
        ]);
        $public = [];
        $locale = App::getLocale();
        if ($surface === 'product') {
            $product = $catalog->productDetail($context['slug'], $locale, now()->getTimestamp());
            abort_if($product === null, 404, __('storefront::messages.product_not_found'));
            $public = ['id' => $product['id']];
        } elseif ($listing) {
            $type = $context['type'];
            $public = ['type' => $type];
            if ($type !== 'search') {
                $slug = $context['slug'];
                $entry = match ($type) {
                    'category' => $catalog->category($slug, $locale),
                    'collection' => $collections->published($slug, $locale),
                    'brand' => $catalog->brand($slug),
                    default => null,
                };
                abort_if($entry === null, 404);
                $public[$type] = ['slug' => $entry['slug']];
            }
        }

        $layout = $builder->resolveLayout($surface, $public);

        return response()->json([
            'data' => array_map(fn (array $zone): array => [
                'key' => $zone['key'],
                'blocks' => array_map(fn (array $block): array => [
                    'type' => $block['type'], 'data' => (object) self::json($block['data']),
                ], $zone['blocks']),
            ], $layout['zones']),
            'meta' => ['surface' => $surface, 'configured' => $layout['configured']],
        ])->header('Cache-Control', 'private, no-store');
    }

    /** Dữ liệu view của khối → JSON: HTML (Htmlable) thành chuỗi, object thành mảng. */
    private static function json(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Htmlable => $value->toHtml(),
            $value instanceof JsonSerializable => self::json($value->jsonSerialize()),
            $value instanceof Arrayable => self::json($value->toArray()),
            is_array($value) => array_map(self::json(...), $value),
            is_object($value) => self::json(get_object_vars($value)),
            default => $value,
        };
    }
}
