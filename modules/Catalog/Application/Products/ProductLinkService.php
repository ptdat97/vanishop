<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Persistence\Models\ProductLinkGroup;
use Modules\Catalog\Persistence\Models\ProductLinkGroupItem;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Liên kết giữa các sản phẩm theo loại (tương tự, mua kèm…) — phỏng theo Links của Vanilo (MIT, xem modules/Catalog/NOTICE).
 *
 * Mỗi liên kết là một nhóm: hai chiều thì mọi thành viên thấy nhau (vd. "cùng mẫu khác chất liệu"), một chiều thì chỉ
 * sản phẩm gốc thấy các thành viên (vd. "mua kèm": áo gợi ý quần, quần không gợi ý ngược lại). Khác Vanilo: thêm liên kết
 * luôn vào nhóm đúng chiều của sản phẩm gốc, và gỡ liên kết chỉ đụng nhóm mà sản phẩm gốc nhìn thấy.
 */
final class ProductLinkService
{
    public const MAX_LINKS_PER_TYPE = 24;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  list<string>  $productCodes
     * @return int số sản phẩm được liên kết thêm
     */
    public function link(Product $product, LinkType $type, array $productCodes, bool $oneWay): int
    {
        $codes = array_values(array_unique(array_filter(array_map(trim(...), $productCodes), fn (string $code): bool => $code !== '')));
        $targets = Product::query()->whereIn('product_code', $codes)->pluck('id', 'product_code');
        $missing = array_values(array_diff($codes, $targets->keys()->all()));
        if ($missing !== []) {
            throw ValidationException::withMessages(['product_codes' => __('catalog::messages.product_codes_not_found', ['codes' => implode(', ', $missing)])]);
        }

        $current = $this->linkedProductIds($product->id)[$type->id] ?? [];
        $new = array_values(array_diff($targets->map(fn ($id): int => (int) $id)->values()->all(), [...$current, $product->id]));
        if (count($current) + count($new) > self::MAX_LINKS_PER_TYPE) {
            throw ValidationException::withMessages(['product_codes' => __('catalog::messages.too_many_links', ['max' => self::MAX_LINKS_PER_TYPE])]);
        }
        if ($new === []) {
            return 0;
        }

        DB::transaction(function () use ($product, $type, $oneWay, $new): void {
            $group = $this->ownGroup($product, $type, $oneWay);
            $position = (int) $group->items()->max('position');
            foreach ($new as $productId) {
                $group->items()->create(['product_id' => $productId, 'position' => ++$position]);
            }
            $this->audit->record('catalog.product.links_added', 'product', $product->id, ['type' => $type->code, 'product_ids' => $new, 'one_way' => $oneWay]);
        });

        return count($new);
    }

    /**
     * Gỡ `$linked` khỏi các nhóm loại `$type` mà `$product` nhìn thấy. Nhóm hai chiều: các thành viên khác cũng không còn
     * thấy `$linked`. Nhóm chỉ còn một sản phẩm thì bị xoá.
     */
    public function unlink(Product $product, LinkType $type, Product $linked): void
    {
        DB::transaction(function () use ($product, $type, $linked): void {
            foreach ($this->visibleGroups($product->id)->where('link_type_id', $type->id) as $group) {
                ProductLinkGroupItem::query()->where('product_link_group_id', $group->id)->where('product_id', $linked->id)->delete();
                if (ProductLinkGroupItem::query()->where('product_link_group_id', $group->id)->count() < 2) {
                    $group->delete();
                }
            }
            $this->audit->record('catalog.product.link_removed', 'product', $product->id, ['type' => $type->code, 'product_id' => $linked->id]);
        });
    }

    /**
     * Sản phẩm `$productId` nhìn thấy, theo loại liên kết, giữ thứ tự thêm; không gồm chính nó, không trùng.
     *
     * @return array<int, list<int>> link_type_id => product id
     */
    public function linkedProductIds(int $productId): array
    {
        $linked = [];
        foreach ($this->visibleGroups($productId) as $group) {
            foreach ($group->items as $item) {
                if ($item->product_id !== $productId) {
                    $linked[$group->link_type_id][] = $item->product_id;
                }
            }
        }

        return array_map(fn (array $ids): array => array_values(array_unique($ids)), $linked);
    }

    /**
     * @return Collection<int, ProductLinkGroup>
     */
    private function visibleGroups(int $productId): Collection
    {
        return ProductLinkGroup::query()
            ->with('items')
            ->whereIn('id', ProductLinkGroupItem::query()->select('product_link_group_id')->where('product_id', $productId))
            ->where(fn ($query) => $query->whereNull('root_product_id')->orWhere('root_product_id', $productId))
            ->orderBy('id')
            ->get();
    }

    /**
     * Nhóm đúng chiều mà `$product` là gốc (một chiều) hoặc thành viên (hai chiều); chưa có thì tạo, kèm `$product` ở đầu.
     */
    private function ownGroup(Product $product, LinkType $type, bool $oneWay): ProductLinkGroup
    {
        $group = ProductLinkGroup::query()
            ->where('link_type_id', $type->id)
            ->when(
                $oneWay,
                fn ($query) => $query->where('root_product_id', $product->id),
                fn ($query) => $query->whereNull('root_product_id')->whereHas('items', fn ($items) => $items->where('product_id', $product->id)),
            )
            ->orderBy('id')
            ->first();

        if ($group === null) {
            $group = ProductLinkGroup::query()->create(['link_type_id' => $type->id, 'root_product_id' => $oneWay ? $product->id : null]);
            $group->items()->create(['product_id' => $product->id, 'position' => 0]);
        }

        return $group;
    }
}
