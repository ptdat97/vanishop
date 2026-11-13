<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Collection;
use Modules\Catalog\Application\Media\ImageSizes;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\SellableVariant;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Domain\OptionDisplayType;
use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleAttributeValue;
use Modules\Catalog\Persistence\Models\StyleOption;
use Modules\Catalog\Persistence\Models\StyleOptionValue;
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
            ->with(['translations', 'brand', 'options.type.translations', 'optionValues.value.translations', 'primaryCategory', 'attributeValues.attribute.translations', 'attributeValues.value.translations', 'videos',
                // Mọi SKU (cả ngừng bán) cho `variants`; thẻ và tổng hợp chỉ tính SKU đang bán.
                'variants' => fn ($query) => $query->with(['optionValues', 'gallery.media'])])
            ->whereLocalizedSlug($slug, $locale)
            ->first();

        if ($style === null || ! PublishWindow::isVisible($style->status, $style->publishWindow(), new \DateTimeImmutable("@{$now}"))) {
            return null;
        }

        return [
            // Swatch của thẻ (`colors`) thay bằng trục chọn `options` ở trang chi tiết.
            ...array_diff_key($this->summary($style, $locale), ['colors' => true]),
            'description' => $style->translate('description', $locale),
            'care_instructions' => $style->translate('care_instructions', $locale),
            'meta_title' => $style->translate('meta_title', $locale),
            'meta_description' => $style->translate('meta_description', $locale),
            // Slug theo từng ngôn ngữ (ADR-038): URL tương ứng của cùng sản phẩm, hreflang.
            'slugs' => $style->slugs(),
            'breadcrumb' => $this->breadcrumb($style->primaryCategory, $locale),
            'attributes' => $this->attributes($style->attributeValues, $locale),
            ...$this->variantModel($style, $locale),
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
            ->with(['style.translations', 'style.brand', 'gallery.media', ...VariantOptions::relations()])
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
                imageUrl: $variant->gallery->first()?->media->url(400, ImageSizes::PRODUCT),
                brandName: $variant->style->brand?->name,
                weightGram: $variant->weight_gram,
                options: VariantOptions::present($variant, $locale),
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
        return ['translations', 'brand', 'options.type', 'optionValues.value.translations',
            'variants' => fn ($query) => $query->where('status', 'active')->with(['optionValues', 'gallery.media'])];
    }

    private function summary(Style $style, string $locale): array
    {
        $firstImage = $style->variants->flatMap(fn (Variant $variant) => $variant->gallery)->first();

        return [
            'id' => $style->id,
            'slug' => $style->slugFor($locale),
            // Ngôn ngữ đã dịch (ADR-038): sitemap, hreflang, noindex cho trang chưa dịch.
            'locales' => $style->translatedLocales(),
            'style_code' => $style->style_code,
            'name' => $style->translate('name', $locale),
            'brand' => $style->brand === null ? null : ['id' => $style->brand->id, 'slug' => $style->brand->slug, 'name' => $style->brand->name],
            'image_url' => $firstImage?->media->url(400, ImageSizes::PRODUCT),
            // Ảnh responsive (0.3.67): theme ghép `sizes` theo bố cục lưới; null khi ảnh không có bản thu nhỏ.
            'image_srcset' => ($firstImage?->media->srcset(ImageSizes::PRODUCT) ?? '') ?: null,
            // Swatch của thẻ: giá trị của trục kiểu ô màu (nếu có) mà SKU đang bán dùng (ADR-045).
            'colors' => $this->swatches($style, $locale),
            'variant_ids' => $style->variants->where('status', VariantStatus::Active)->pluck('id')->values()->all(),
        ];
    }

    /**
     * Swatch của thẻ sản phẩm: giá trị của trục đầu tiên kiểu ô màu mà SKU (đã nạp) dùng, theo thứ tự của sản phẩm; ảnh của
     * swatch là ảnh đầu tiên của SKU đầu tiên mang giá trị đó.
     *
     * @return list<array{code: string, name: string|null, hex: string|null, family: string, image_url: string|null}>
     */
    private function swatches(Style $style, string $locale): array
    {
        $axis = $style->options->first(fn (StyleOption $option): bool => $option->type->display_type === OptionDisplayType::Swatch);
        if ($axis === null) {
            return [];
        }

        return $this->axisValues($style, $axis, onlyUsed: false)->map(fn (OptionValue $value): array => [
            'code' => $value->code, 'name' => $value->translate('name', $locale), 'hex' => $value->hex, 'family' => (string) ($value->family ?? 'multi'),
            'image_url' => $this->variantsWith($style, $value->id)->flatMap(fn (Variant $variant) => $variant->gallery)->first()?->media->url(400, ImageSizes::PRODUCT),
        ])->values()->all();
    }

    /**
     * Giá trị của một trục theo thứ tự riêng của sản phẩm; `onlyUsed` = chỉ giá trị có SKU (bộ chọn ở trang chi tiết), còn thẻ
     * sản phẩm hiện mọi màu của sản phẩm.
     *
     * @return Collection<int, OptionValue>
     */
    private function axisValues(Style $style, StyleOption $axis, bool $onlyUsed = true): Collection
    {
        $used = $onlyUsed ? $style->variants->flatMap(fn (Variant $variant) => $variant->optionValues)->pluck('id')->unique()->all() : null;

        return $style->optionValues->map(fn (StyleOptionValue $row): OptionValue => $row->value)
            ->filter(fn (OptionValue $value): bool => $value->option_type_id === $axis->option_type_id && ($used === null || in_array($value->id, $used, true)))->values();
    }

    /**
     * @return Collection<int, Variant>
     */
    private function variantsWith(Style $style, int $valueId): Collection
    {
        return $style->variants->filter(fn (Variant $variant): bool => $variant->optionValues->contains('id', $valueId))->values();
    }

    /**
     * Mô hình biến thể của trang chi tiết (options/variants của API thương mại, ADR-045):
     * - `options`: các trục của sản phẩm theo thứ tự (mã loại, tên, `display_type`), giá trị có `id` ổn định, theo thứ tự của sản phẩm.
     * - `variants`: **mọi SKU** (cả ngừng bán: `is_active` = false); `option_values` trỏ giá trị bằng id, không bằng vị trí mảng;
     *   `media` trỏ ảnh của chính SKU trong `media.gallery`.
     * - `media.gallery`: hợp ảnh của mọi SKU, mỗi ảnh một lần (`id` = id media).
     *
     * @return array{options: list<array<string, mixed>>, variants: list<array<string, mixed>>, media: array{gallery: list<array<string, mixed>>}}
     */
    private function variantModel(Style $style, string $locale): array
    {
        $axes = $style->options;
        $types = $axes->mapWithKeys(fn (StyleOption $axis): array => [$axis->option_type_id => $axis->type->code]);
        $positions = $style->optionValues->mapWithKeys(fn (StyleOptionValue $row): array => [$row->option_value_id => $row->position]);
        $ordered = fn (Variant $variant): array => $axes->map(fn (StyleOption $axis): ?OptionValue => $variant->optionValues->first(fn (OptionValue $value): bool => $value->option_type_id === $axis->option_type_id))->filter()->values()->all();
        $variants = $style->variants->sortBy(fn (Variant $variant): string => implode('-', array_map(fn (OptionValue $value): string => sprintf('%05d', $positions[$value->id] ?? 99999), $ordered($variant))))->values();

        $gallery = [];
        foreach ($variants as $variant) {
            foreach ($variant->gallery as $image) {
                $gallery[$image->media_id] ??= [
                    'id' => $image->media_id, 'url' => $image->media->url(800, ImageSizes::PRODUCT), 'srcset' => $image->media->srcset(ImageSizes::PRODUCT) ?: null,
                    'thumb_url' => $image->media->url(160, ImageSizes::PRODUCT), 'alt' => $image->alt,
                ];
            }
        }

        return [
            'options' => $axes->map(fn (StyleOption $axis): array => [
                'code' => $axis->type->code, 'name' => $axis->type->translate('name', $locale) ?? $axis->type->code, 'display_type' => $axis->type->display_type->value,
                'values' => $this->axisValues($style, $axis)->map(fn (OptionValue $value): array => [
                    'id' => $value->id, 'code' => $value->code, 'name' => $value->translate('name', $locale) ?? $value->code,
                    'hex' => $value->hex, 'family' => $value->family, 'group' => $value->group,
                    'image_url' => $this->variantsWith($style, $value->id)->flatMap(fn (Variant $variant) => $variant->gallery)->first()?->media->url(400, ImageSizes::PRODUCT),
                ])->all(),
            ])->values()->all(),
            'variants' => $variants->map(fn (Variant $variant): array => [
                'id' => $variant->id, 'sku' => $variant->sku, 'is_active' => $variant->status === VariantStatus::Active,
                'option_values' => array_map(fn (OptionValue $value): array => ['option' => $types[$value->option_type_id], 'value_id' => $value->id], $ordered($variant)),
                'media' => ['primary_image_id' => $variant->gallery->first()?->media_id, 'image_ids' => $variant->gallery->pluck('media_id')->map(fn ($id): int => (int) $id)->values()->all()],
            ])->all(),
            'media' => ['gallery' => array_values($gallery)],
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
