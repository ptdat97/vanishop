<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleLinkGroup;
use Modules\Catalog\Persistence\Models\StyleLinkGroupItem;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Liên kết giữa các sản phẩm theo loại (tương tự, mua kèm…) — phỏng theo Links của Vanilo (MIT, xem modules/Catalog/NOTICE).
 *
 * Mỗi liên kết là một nhóm: hai chiều thì mọi thành viên thấy nhau (vd. "cùng mẫu khác chất liệu"), một chiều thì chỉ
 * sản phẩm gốc thấy các thành viên (vd. "mua kèm": áo gợi ý quần, quần không gợi ý ngược lại). Khác Vanilo: thêm liên kết
 * luôn vào nhóm đúng chiều của sản phẩm gốc, và gỡ liên kết chỉ đụng nhóm mà sản phẩm gốc nhìn thấy.
 */
final class StyleLinkService
{
    public const MAX_LINKS_PER_TYPE = 24;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  list<string>  $styleCodes
     * @return int số sản phẩm được liên kết thêm
     */
    public function link(Style $style, LinkType $type, array $styleCodes, bool $oneWay): int
    {
        $codes = array_values(array_unique(array_filter(array_map(trim(...), $styleCodes), fn (string $code): bool => $code !== '')));
        $targets = Style::query()->whereIn('style_code', $codes)->pluck('id', 'style_code');
        $missing = array_values(array_diff($codes, $targets->keys()->all()));
        if ($missing !== []) {
            throw ValidationException::withMessages(['style_codes' => __('catalog::messages.style_codes_not_found', ['codes' => implode(', ', $missing)])]);
        }

        $current = $this->linkedStyleIds($style->id)[$type->id] ?? [];
        $new = array_values(array_diff($targets->map(fn ($id): int => (int) $id)->values()->all(), [...$current, $style->id]));
        if (count($current) + count($new) > self::MAX_LINKS_PER_TYPE) {
            throw ValidationException::withMessages(['style_codes' => __('catalog::messages.too_many_links', ['max' => self::MAX_LINKS_PER_TYPE])]);
        }
        if ($new === []) {
            return 0;
        }

        DB::transaction(function () use ($style, $type, $oneWay, $new): void {
            $group = $this->ownGroup($style, $type, $oneWay);
            $position = (int) $group->items()->max('position');
            foreach ($new as $styleId) {
                $group->items()->create(['style_id' => $styleId, 'position' => ++$position]);
            }
            $this->audit->record('catalog.product.links_added', 'style', $style->id, ['type' => $type->code, 'style_ids' => $new, 'one_way' => $oneWay]);
        });

        return count($new);
    }

    /**
     * Gỡ `$linked` khỏi các nhóm loại `$type` mà `$style` nhìn thấy. Nhóm hai chiều: các thành viên khác cũng không còn
     * thấy `$linked`. Nhóm chỉ còn một sản phẩm thì bị xoá.
     */
    public function unlink(Style $style, LinkType $type, Style $linked): void
    {
        DB::transaction(function () use ($style, $type, $linked): void {
            foreach ($this->visibleGroups($style->id)->where('link_type_id', $type->id) as $group) {
                StyleLinkGroupItem::query()->where('style_link_group_id', $group->id)->where('style_id', $linked->id)->delete();
                if (StyleLinkGroupItem::query()->where('style_link_group_id', $group->id)->count() < 2) {
                    $group->delete();
                }
            }
            $this->audit->record('catalog.product.link_removed', 'style', $style->id, ['type' => $type->code, 'style_id' => $linked->id]);
        });
    }

    /**
     * Sản phẩm `$styleId` nhìn thấy, theo loại liên kết, giữ thứ tự thêm; không gồm chính nó, không trùng.
     *
     * @return array<int, list<int>> link_type_id => style id
     */
    public function linkedStyleIds(int $styleId): array
    {
        $linked = [];
        foreach ($this->visibleGroups($styleId) as $group) {
            foreach ($group->items as $item) {
                if ($item->style_id !== $styleId) {
                    $linked[$group->link_type_id][] = $item->style_id;
                }
            }
        }

        return array_map(fn (array $ids): array => array_values(array_unique($ids)), $linked);
    }

    /**
     * @return Collection<int, StyleLinkGroup>
     */
    private function visibleGroups(int $styleId): Collection
    {
        return StyleLinkGroup::query()
            ->with('items')
            ->whereIn('id', StyleLinkGroupItem::query()->select('style_link_group_id')->where('style_id', $styleId))
            ->where(fn ($query) => $query->whereNull('root_style_id')->orWhere('root_style_id', $styleId))
            ->orderBy('id')
            ->get();
    }

    /**
     * Nhóm đúng chiều mà `$style` là gốc (một chiều) hoặc thành viên (hai chiều); chưa có thì tạo, kèm `$style` ở đầu.
     */
    private function ownGroup(Style $style, LinkType $type, bool $oneWay): StyleLinkGroup
    {
        $group = StyleLinkGroup::query()
            ->where('link_type_id', $type->id)
            ->when(
                $oneWay,
                fn ($query) => $query->where('root_style_id', $style->id),
                fn ($query) => $query->whereNull('root_style_id')->whereHas('items', fn ($items) => $items->where('style_id', $style->id)),
            )
            ->orderBy('id')
            ->first();

        if ($group === null) {
            $group = StyleLinkGroup::query()->create(['link_type_id' => $type->id, 'root_style_id' => $oneWay ? $style->id : null]);
            $group->items()->create(['style_id' => $style->id, 'position' => 0]);
        }

        return $group;
    }
}
