<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Collection;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\SellableVariant;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleAttributeValue;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Persistence\Models\Video;
use Modules\Extension\Facades\Hook;

/**
 * Dữ liệu sản phẩm cho storefront (native và Storefront API dùng chung — ADR-009).
 * Chỉ trả sản phẩm đang hiển thị; thuộc tính internal không bao giờ lộ ra.
 */
final class StorefrontProductQuery
{
    public function __construct(
        private readonly SearchManager $search,
        private readonly StyleLinkService $links,
        private readonly VideoDrivers $videos,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int, facets: array<string, mixed>}
     */
    public function list(ProductSearchQuery $query, string $locale): array
    {
        $filtered = Hook::filter('vani.catalog.listing.query', $query);
        $result = $this->search->search($filtered instanceof ProductSearchQuery ? $filtered : $query);

        $styles = Style::query()
            ->with(self::summaryRelations())
            ->whereIn('id', $result->styleIds)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($result->styleIds as $id) {
            $style = $styles->get($id);
            if ($style !== null) {
                $items[] = $this->summary($style, $locale);
            }
        }

        return ['items' => $items, 'total' => $result->total, 'facets' => $result->facets];
    }

    /**
     * Sản phẩm đang hiển thị theo id style, giữ thứ tự `$styleIds`; id trùng/không hiển thị bị bỏ.
     *
     * @param  list<int>  $styleIds
     * @return list<array<string, mixed>>
     */
    public function byIds(array $styleIds, string $locale, int $now): array
    {
        $ids = array_values(array_unique(array_filter($styleIds, fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $at = new \DateTimeImmutable("@{$now}");
        $styles = Style::query()->with(self::summaryRelations())->whereIn('id', $ids)->get()
            ->filter(fn (Style $style): bool => PublishWindow::isVisible($style->status, $style->publishWindow(), $at))
            ->keyBy('id');

        $items = [];
        foreach ($ids as $id) {
            if ($styles->has($id)) {
                $items[] = $this->summary($styles->get($id), $locale);
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $slug, string $locale, int $now): ?array
    {
        $style = Style::query()
            ->with(['translations', 'brand', 'colors.color.translations', 'colors.gallery.media', 'primaryCategory', 'attributeValues.attribute.translations', 'attributeValues.value.translations', 'videos',
                'variants' => fn ($query) => $query->where('status', 'active')->with(['size', 'styleColor.color'])])
            ->whereLocalizedSlug($slug, $locale)
            ->first();

        if ($style === null || ! PublishWindow::isVisible($style->status, $style->publishWindow(), new \DateTimeImmutable("@{$now}"))) {
            return null;
        }

        return [
            ...$this->summary($style, $locale),
            'description' => $style->translate('description', $locale),
            'care_instructions' => $style->translate('care_instructions', $locale),
            'meta_title' => $style->translate('meta_title', $locale),
            'meta_description' => $style->translate('meta_description', $locale),
            // Slug theo từng ngôn ngữ (ADR-038): URL tương ứng của cùng sản phẩm, hreflang.
            'slugs' => $style->slugs(),
            'breadcrumb' => $this->breadcrumb($style->primaryCategory, $locale),
            'attributes' => $this->attributes($style->attributeValues, $locale),
            'colors' => $style->colors->map(fn (StyleColor $styleColor): array => [
                ...$this->color($styleColor, $locale),
                'images' => $styleColor->gallery->map(fn ($image): array => ['url' => $image->media->url(800), 'alt' => $image->alt])->all(),
            ])->all(),
            'variants' => $style->variants
                ->sortBy(fn (Variant $variant): string => sprintf('%05d-%05d', $variant->styleColor->position, $variant->size->sort_order))
                ->map(fn (Variant $variant): array => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'color_code' => $variant->styleColor->color->code,
                    'size_code' => $variant->size->code,
                    'size_system' => $variant->size->size_system->value,
                ])->values()->all(),
            'videos' => array_values(array_filter($style->videos->map(fn (Video $video): ?array => $this->videos->view($video))->all())),
            'links' => $this->links($style, $locale),
        ];
    }

    /**
     * Sản phẩm liên kết theo loại đang bật, theo thứ tự loại; chỉ có id style — tầng ghép lọc sản phẩm không hiển thị
     * và dựng thẻ sản phẩm (giá, tồn) bằng `productsByIds`.
     *
     * @return list<array{code: string, name: string|null, style_ids: list<int>}>
     */
    private function links(Style $style, string $locale): array
    {
        $linked = $this->links->linkedStyleIds($style->id);
        if ($linked === []) {
            return [];
        }

        return LinkType::query()->with('translations')->whereKey(array_keys($linked))->where('is_active', true)->orderBy('position')->orderBy('id')->get()
            ->map(fn (LinkType $type): array => ['code' => $type->code, 'name' => $type->translate('name', $locale), 'style_ids' => $linked[$type->id]])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<int, SellableVariant>
     */
    public function sellable(array $variantIds, string $locale, int $now): array
    {
        $at = new \DateTimeImmutable("@{$now}");

        return Variant::query()
            ->with(['style.translations', 'style.brand', 'styleColor.color.translations', 'styleColor.gallery.media', 'size'])
            ->whereIn('id', $variantIds)
            ->where('status', VariantStatus::Active)
            ->get()
            ->filter(fn (Variant $variant): bool => PublishWindow::isVisible($variant->style->status, $variant->style->publishWindow(), $at))
            ->mapWithKeys(fn (Variant $variant): array => [$variant->id => new SellableVariant(
                id: $variant->id,
                brandId: $variant->style->brand_id,
                styleId: $variant->style_id,
                sku: $variant->sku,
                slug: $variant->style->slugFor($locale),
                name: (string) $variant->style->translate('name', $locale),
                colorCode: $variant->styleColor->color->code,
                colorName: $variant->styleColor->color->translate('name', $locale),
                sizeCode: $variant->size->code,
                imageUrl: $variant->styleColor->gallery->first()?->media->url(400),
                brandName: $variant->style->brand?->name,
                weightGram: $variant->weight_gram,
            )])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<int|string, mixed>
     */
    private static function summaryRelations(): array
    {
        return ['translations', 'brand', 'colors.color.translations', 'colors.gallery.media', 'variants' => fn ($query) => $query->where('status', 'active')];
    }

    private function summary(Style $style, string $locale): array
    {
        $firstImage = $style->colors->flatMap(fn (StyleColor $color) => $color->gallery)->first();

        return [
            'id' => $style->id,
            'slug' => $style->slugFor($locale),
            // Ngôn ngữ đã dịch (ADR-038): sitemap, hreflang, noindex cho trang chưa dịch.
            'locales' => $style->translatedLocales(),
            'style_code' => $style->style_code,
            'name' => $style->translate('name', $locale),
            'brand' => $style->brand === null ? null : ['id' => $style->brand->id, 'slug' => $style->brand->slug, 'name' => $style->brand->name],
            'image_url' => $firstImage?->media->url(400),
            // Ảnh responsive (0.3.67): theme ghép `sizes` theo bố cục lưới; null khi ảnh không có bản thu nhỏ.
            'image_srcset' => ($firstImage?->media->srcset() ?? '') ?: null,
            'colors' => $style->colors->map(fn (StyleColor $styleColor): array => $this->color($styleColor, $locale))->all(),
            'variant_ids' => $style->variants->where('status', VariantStatus::Active)->pluck('id')->values()->all(),
        ];
    }

    /**
     * @return array{code: string, name: string|null, hex: string|null, family: string, image_url: string|null}
     */
    private function color(StyleColor $styleColor, string $locale): array
    {
        return [
            'code' => $styleColor->color->code,
            'name' => $styleColor->color->translate('name', $locale),
            'hex' => $styleColor->color->hex,
            'family' => $styleColor->color->color_family->value,
            'image_url' => $styleColor->gallery->first()?->media->url(400),
        ];
    }

    /**
     * @return list<array{slug: string, name: string|null}>
     */
    private function breadcrumb(?Category $category, string $locale): array
    {
        if ($category === null) {
            return [];
        }

        $ancestors = Category::query()->with('translations')->whereIn('id', $category->categoryPath()->ids)->get()->keyBy('id');

        return array_values(array_filter(array_map(
            fn (int $id): ?array => $ancestors->has($id) ? ['id' => $id, 'slug' => $ancestors[$id]->slugFor($locale), 'name' => $ancestors[$id]->translate('name', $locale)] : null,
            $category->categoryPath()->ids,
        )));
    }

    /**
     * @param  Collection<int, StyleAttributeValue>  $rows
     * @return list<array{code: string, name: string|null, value: string|bool|list<string|null>|null}>
     */
    private function attributes(Collection $rows, string $locale): array
    {
        return $rows
            ->filter(fn (StyleAttributeValue $row): bool => $row->attribute->kind === AttributeKind::Spec)
            ->groupBy('attribute_id')
            ->map(function (Collection $group) use ($locale): array {
                $attribute = $group->first()->attribute;

                $value = match ($attribute->input_type) {
                    AttributeInputType::Multiselect => $group->map(fn ($row) => $row->value?->translate('label', $locale))->values()->all(),
                    AttributeInputType::Select => $group->first()->value?->translate('label', $locale),
                    AttributeInputType::Boolean => (bool) $group->first()->value_bool,
                    AttributeInputType::Text => $group->first()->value_text,
                };

                return ['code' => $attribute->code, 'name' => $attribute->translate('name', $locale), 'value' => $value, 'position' => $attribute->position];
            })
            ->sortBy('position')
            ->map(fn (array $item): array => ['code' => $item['code'], 'name' => $item['name'], 'value' => $item['value']])
            ->values()
            ->all();
    }
}
