<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;
use Modules\Storefront\Contracts\BuilderSurface;

/**
 * Một vùng nhận block của {@see BuilderSurface}.
 *
 * - `BuilderZone::slot()` gắn vào slot storefront đã có (Core hoặc plugin khai báo `builder: true` trong hooks.php):
 *   Builder Engine render block tại `<x-vani::hook-slot>`, trước phần tử plugin chèn bằng listener `onSlot`.
 * - `BuilderZone::page()` là vùng do controller của trang tự render (vd. thân trang chủ).
 *
 *   BuilderZone::slot('vani.storefront.pdp.after_details', 'Sau mô tả sản phẩm', types: ['rich_text'], max: 2)
 */
#[PluginApi]
final readonly class BuilderZone
{
    /**
     * @param  list<string>|null  $types  loại block được đặt (null = mọi loại)
     * @param  int|null  $max  số block tối đa (null = theo giới hạn chung)
     */
    private function __construct(
        public string $key,
        public string $label,
        public ?string $slot,
        public ?array $types,
        public ?int $max,
    ) {}

    /**
     * @param  list<string>|null  $types
     */
    public static function slot(string $slot, string $label, ?array $types = null, ?int $max = null): self
    {
        return new self($slot, $label, $slot, $types, $max);
    }

    /**
     * @param  list<string>|null  $types
     */
    public static function page(string $key, string $label, ?array $types = null, ?int $max = null): self
    {
        return new self($key, $label, null, $types, $max);
    }
}
