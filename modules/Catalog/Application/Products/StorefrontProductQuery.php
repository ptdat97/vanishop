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
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleAttributeValue;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Catalog\Persistence\Models\Variant;

/**
 * Dữ liệu sản phẩm cho storefront (native và Storefront API dùng chung — ADR-009).
 * Chỉ trả sản phẩm đang hiển thị; thuộc tính internal không bao giờ lộ ra.
 */
final class StorefrontProductQuery
{
    public function __construct(private readonly SearchManager $search) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int, facets: array<string, mixed>}
     */
    public function list(ProductSearchQuery $query, string $locale): array
    {
        $result = $this->search->search($query);

        $styles = Style::query()
            ->with(['translations', 'colors.color.translations', 'colors.gallery.media', 'variants' => fn ($query) => $query->where('status', 'active')])
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
     * @return array<string, mixed>|null
     */
    public function detail(string $slug, string $locale, int $now): ?array
    {
        $style = Style::query()
            ->with(['translations', 'colors.color.translations', 'colors.gallery.media', 'primaryCategory', 'attributeValues.attribute.translations', 'attributeValues.value.translations',
                'variants' => fn ($query) => $query->where('status', 'active')->with(['size', 'styleColor.color'])])
            ->where('slug', $slug)
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
            'breadcrumb' => $this->breadcrumb($style->primaryCategory, $locale),
            'attributes' => $this->attributes($style->attributeValues, $locale),
            'colors' => $style->colors->map(fn (StyleColor $styleColor): array => [
                ...$this->color($styleColor, $locale),
                'images' => $styleColor->gallery->map(fn ($image): array => ['url' => $image->media->url(), 'alt' => $image->alt])->all(),
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
        ];
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<int, SellableVariant>
     */
    public function sellable(array $variantIds, string $locale, int $now): array
    {
        $at = new \DateTimeImmutable("@{$now}");

        return Variant::query()
            ->with(['style.translations', 'styleColor.color.translations', 'styleColor.gallery.media', 'size'])
            ->whereIn('id', $variantIds)
            ->where('status', VariantStatus::Active)
            ->get()
            ->filter(fn (Variant $variant): bool => PublishWindow::isVisible($variant->style->status, $variant->style->publishWindow(), $at))
            ->mapWithKeys(fn (Variant $variant): array => [$variant->id => new SellableVariant(
                id: $variant->id,
                brandId: $variant->brand_id,
                styleId: $variant->style_id,
                sku: $variant->sku,
                slug: $variant->style->slug,
                name: (string) $variant->style->translate('name', $locale),
                colorCode: $variant->styleColor->color->code,
                colorName: $variant->styleColor->color->translate('name', $locale),
                sizeCode: $variant->size->code,
                imageUrl: $variant->styleColor->gallery->first()?->media->url(),
            )])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Style $style, string $locale): array
    {
        $firstImage = $style->colors->flatMap(fn (StyleColor $color) => $color->gallery)->first();

        return [
            'id' => $style->id,
            'slug' => $style->slug,
            'style_code' => $style->style_code,
            'name' => $style->translate('name', $locale),
            'image_url' => $firstImage?->media->url(),
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
            'image_url' => $styleColor->gallery->first()?->media->url(),
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
            fn (int $id): ?array => $ancestors->has($id) ? ['slug' => $ancestors[$id]->slug, 'name' => $ancestors[$id]->translate('name', $locale)] : null,
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
