<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use JsonSerializable;
use Modules\Storefront\Application\Menus\MenuPresenter;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Application\StoreProfile;
use Modules\Storefront\Application\Theme\Themes;

/**
 * "Vỏ" trang cho client không phải Blade (ADR-044, api-first-refactor đợt A2): thông tin cửa hàng, menu theo vị trí, trang
 * chủ dựng bằng khối. Cùng use-case với layout và trang chủ của Blade.
 */
final class StoreController
{
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
    public function home(PageBlocks $blocks): JsonResponse
    {
        $configured = $blocks->home();

        return response()->json([
            'data' => $configured === null ? null : array_map(fn (array $block): array => ['type' => $block['type'], 'data' => self::json($block['data'])], $blocks->resolve($configured)),
            'meta' => ['configured' => $configured !== null],
        ]);
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
