<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Catalog\Application\Media\ImageSizes;
use Modules\Catalog\Application\Search\ProductSearch;
use Modules\Catalog\Contracts\Data\DefaultWeight;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\SellableVariant;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Domain\OptionDisplayType;
use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\Mediable;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Persistence\Models\ProductAttributeValue;
use Modules\Catalog\Persistence\Models\ProductOption;
use Modules\Catalog\Persistence\Models\ProductOptionValue;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Persistence\Models\Video;
use Modules\Extension\Facades\Hook;
use Modules\Tenancy\Contracts\Settings;

/**
 * Dữ liệu sản phẩm cho storefront (native và Storefront API dùng chung — ADR-009).
 * Chỉ trả sản phẩm đang hiển thị; thuộc tính internal không bao giờ lộ ra. Scoped: thương hiệu và loại tuỳ chọn của thẻ tra một
 * lần mỗi request (trang chủ nhiều khối sản phẩm dùng chung vài thương hiệu/loại).
 */
final class StorefrontProductQuery
{
    /** @var array<int, Brand> */
    private array $brands = [];

    /** @var array<int, OptionType> */
    private array $optionTypes = [];

    /** @var array<int, OptionValue> giá trị tuỳ chọn (kèm bản dịch) đã nạp trong request — các khối sản phẩm dùng chung */
    private array $optionValues = [];

    public function __construct(
        private readonly ProductSearch $search,
        private readonly ProductLinkService $links,
        private readonly VideoDrivers $videos,
        private readonly Settings $settings,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int, facets: array<string, mixed>}
     */
    public function list(ProductSearchQuery $query, string $locale): array
    {
        $filtered = Hook::filter('vani.catalog.listing.query', $query);
        $result = $this->search->search($filtered instanceof ProductSearchQuery ? $filtered : $query);

        $products = $this->withReferences(Product::query()
            ->with(self::summaryRelations())
            ->whereIn('id', $result->productIds)
            ->get())
            ->keyBy('id');

        $items = [];
        foreach ($result->productIds as $id) {
            $product = $products->get($id);
            if ($product !== null) {
                $items[] = $this->summary($product, $locale);
            }
        }

        return ['items' => $items, 'total' => $result->total, 'facets' => $result->facets];
    }

    /**
     * Chỉ id sản phẩm của trang (không đếm, không facet, không nạp sản phẩm) — cùng hook truy vấn với `list`.
     *
     * @return list<int>
     */
    public function ids(ProductSearchQuery $query): array
    {
        $filtered = Hook::filter('vani.catalog.listing.query', $query);

        return $this->search->ids($filtered instanceof ProductSearchQuery ? $filtered : $query);
    }

    /**
     * Sản phẩm đang hiển thị theo id product, giữ thứ tự `$productIds`; id trùng/không hiển thị bị bỏ.
     *
     * @param  list<int>  $productIds
     * @return list<array<string, mixed>>
     */
    public function byIds(array $productIds, string $locale, int $now): array
    {
        $ids = array_values(array_unique(array_filter($productIds, fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $at = new \DateTimeImmutable("@{$now}");
        $products = $this->withReferences(Product::query()->with(self::summaryRelations())->whereIn('id', $ids)->get())
            ->filter(fn (Product $product): bool => PublishWindow::isVisible($product->status, $product->publishWindow(), $at))
            ->keyBy('id');

        $items = [];
        foreach ($ids as $id) {
            if ($products->has($id)) {
                $items[] = $this->summary($products->get($id), $locale);
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $slug, string $locale, int $now): ?array
    {
        $product = Product::query()
            ->with(['translations', 'brand', 'options.type.translations', 'optionValues.value.translations', 'primaryCategory', 'attributeValues.attribute.translations', 'attributeValues.value.translations', 'videos',
                // Mọi SKU (cả ngừng bán) cho `variants`; thẻ và tổng hợp chỉ tính SKU đang bán.
                'variants' => fn ($query) => $query->with(['optionValues', 'gallery.media.poster'])])
            ->whereLocalizedSlug($slug, $locale)
            ->first();

        if ($product === null || ! PublishWindow::isVisible($product->status, $product->publishWindow(), new \DateTimeImmutable("@{$now}"))) {
            return null;
        }

        return [
            // Swatch của thẻ (`colors`) thay bằng trục chọn `options` ở trang chi tiết.
            ...array_diff_key($this->summary($product, $locale), ['colors' => true]),
            'description' => $product->translate('description', $locale),
            'care_instructions' => $product->translate('care_instructions', $locale),
            'meta_title' => $product->translate('meta_title', $locale),
            'meta_description' => $product->translate('meta_description', $locale),
            // Slug theo từng ngôn ngữ (ADR-038): URL tương ứng của cùng sản phẩm, hreflang.
            'slugs' => $product->slugs(),
            'breadcrumb' => $this->breadcrumb($product->primaryCategory, $locale),
            'attributes' => $this->attributes($product->attributeValues, $locale),
            ...$this->variantModel($product, $locale),
            'videos' => array_values(array_filter($product->videos->map(fn (Video $video): ?array => $this->videos->view($video))->all())),
            'links' => $this->links($product, $locale),
        ];
    }

    /**
     * Sản phẩm liên kết theo loại đang bật, theo thứ tự loại; chỉ có id product — tầng ghép lọc sản phẩm không hiển thị
     * và dựng thẻ sản phẩm (giá, tồn) bằng `productsByIds`.
     *
     * @return list<array{code: string, name: string|null, product_ids: list<int>}>
     */
    private function links(Product $product, string $locale): array
    {
        $linked = $this->links->linkedProductIds($product->id);
        if ($linked === []) {
            return [];
        }

        return LinkType::query()->with('translations')->whereKey(array_keys($linked))->where('is_active', true)->orderBy('position')->orderBy('id')->get()
            ->map(fn (LinkType $type): array => ['code' => $type->code, 'name' => $type->translate('name', $locale), 'product_ids' => $linked[$type->id]])
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
        $defaultWeight = DefaultWeight::grams($this->settings);

        return Variant::query()
            ->with(['product.translations', 'product.brand', 'gallery.media.poster', ...VariantOptions::relations()])
            ->whereIn('id', $variantIds)
            ->where('status', VariantStatus::Active)
            ->get()
            ->filter(fn (Variant $variant): bool => PublishWindow::isVisible($variant->product->status, $variant->product->publishWindow(), $at))
            ->mapWithKeys(fn (Variant $variant): array => [$variant->id => new SellableVariant(
                id: $variant->id,
                brandId: $variant->product->brand_id,
                productId: $variant->product_id,
                sku: $variant->sku,
                slug: $variant->product->slugFor($locale),
                name: (string) $variant->product->translate('name', $locale),
                imageUrl: $variant->gallery->first()?->media->url(400, ImageSizes::PRODUCT),
                brandName: $variant->product->brand?->name,
                // SKU chưa nhập khối lượng → khối lượng mặc định của cửa hàng (tính phí giao, file gửi hãng).
                weightGram: $variant->weight_gram > 0 ? $variant->weight_gram : $defaultWeight,
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
        return ['translations', 'options', 'optionValues',
            'variants' => fn ($query) => $query->where('status', 'active')->with(['optionValues', 'gallery.media.poster'])];
    }

    /**
     * Gắn `brand`, `options.type` và `optionValues.value` (kèm bản dịch) từ bộ nhớ của request (chỉ tra id chưa có): trang
     * chủ nhiều khối sản phẩm không tra lại cùng thương hiệu, loại và giá trị tuỳ chọn.
     *
     * @param  EloquentCollection<int, Product>  $products
     * @return EloquentCollection<int, Product>
     */
    private function withReferences(EloquentCollection $products): EloquentCollection
    {
        $brandIds = array_diff(array_unique(array_filter($products->pluck('brand_id')->map(fn ($id): int => (int) $id)->all())), array_keys($this->brands));
        if ($brandIds !== []) {
            $this->brands += Brand::query()->whereIn('id', $brandIds)->get()->keyBy('id')->all();
        }
        $options = $products->flatMap(fn (Product $product) => $product->options);
        $typeIds = array_diff(array_unique($options->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all()), array_keys($this->optionTypes));
        if ($typeIds !== []) {
            $this->optionTypes += OptionType::query()->whereIn('id', $typeIds)->get()->keyBy('id')->all();
        }
        foreach ($products as $product) {
            $product->setRelation('brand', $this->brands[(int) $product->brand_id] ?? null);
        }
        foreach ($options as $option) {
            $option->setRelation('type', $this->optionTypes[(int) $option->option_type_id] ?? null);
        }
        $rows = $products->flatMap(fn (Product $product) => $product->optionValues);
        $valueIds = array_diff(array_unique($rows->pluck('option_value_id')->map(fn ($id): int => (int) $id)->all()), array_keys($this->optionValues));
        if ($valueIds !== []) {
            $this->optionValues += OptionValue::query()->with('translations')->whereIn('id', $valueIds)->get()->keyBy('id')->all();
        }
        foreach ($rows as $row) {
            $row->setRelation('value', $this->optionValues[(int) $row->option_value_id] ?? null);
        }

        return $products;
    }

    private function summary(Product $product, string $locale): array
    {
        // Ảnh đại diện của thẻ: ảnh đầu tiên của gallery; gallery chỉ có video → poster của video đầu tiên có poster (L24).
        $items = $product->variants->flatMap(fn (Variant $variant) => $variant->gallery);
        $firstImage = $items->first(fn ($item): bool => ! $item->media->isVideo());
        $firstPoster = $firstImage === null ? $items->map(fn ($item) => $item->media->poster)->filter()->first() : null;
        $cover = $firstImage === null ? $firstPoster : $firstImage->media;

        return [
            'id' => $product->id,
            'slug' => $product->slugFor($locale),
            // Ngôn ngữ đã dịch (ADR-038): sitemap, hreflang, noindex cho trang chưa dịch.
            'locales' => $product->translatedLocales(),
            'product_code' => $product->product_code,
            'name' => $product->translate('name', $locale),
            'brand' => $product->brand === null ? null : ['id' => $product->brand->id, 'slug' => $product->brand->slug, 'name' => $product->brand->name],
            'image_url' => $cover?->url(400, ImageSizes::PRODUCT),
            // Ảnh responsive (0.3.67): theme ghép `sizes` theo bố cục lưới; null khi ảnh không có bản thu nhỏ.
            'image_srcset' => ($cover?->srcset(ImageSizes::PRODUCT) ?? '') ?: null,
            // Swatch của thẻ: giá trị của trục kiểu ô màu (nếu có) mà SKU đang bán dùng (ADR-045).
            'colors' => $this->swatches($product, $locale),
            'variant_ids' => $product->variants->where('status', VariantStatus::Active)->pluck('id')->values()->all(),
        ];
    }

    /**
     * Swatch của thẻ sản phẩm: giá trị của trục đầu tiên kiểu ô màu mà SKU (đã nạp) dùng, theo thứ tự của sản phẩm; ảnh của
     * swatch là ảnh đầu tiên của SKU đầu tiên mang giá trị đó.
     *
     * @return list<array{code: string, name: string|null, hex: string|null, family: string, image_url: string|null}>
     */
    private function swatches(Product $product, string $locale): array
    {
        $axis = $product->options->first(fn (ProductOption $option): bool => $option->type->display_type === OptionDisplayType::Swatch);
        if ($axis === null) {
            return [];
        }

        return $this->axisValues($product, $axis, onlyUsed: false)->map(fn (OptionValue $value): array => [
            'code' => $value->code, 'name' => $value->translate('name', $locale), 'hex' => $value->hex, 'family' => (string) ($value->family ?? 'multi'),
            'image_url' => self::imageUrl($this->variantsWith($product, $value->id)->flatMap(fn (Variant $variant) => $variant->gallery), 400),
        ])->values()->all();
    }

    /**
     * Giá trị của một trục theo thứ tự riêng của sản phẩm; `onlyUsed` = chỉ giá trị có SKU (bộ chọn ở trang chi tiết), còn thẻ
     * sản phẩm hiện mọi màu của sản phẩm.
     *
     * @return Collection<int, OptionValue>
     */
    private function axisValues(Product $product, ProductOption $axis, bool $onlyUsed = true): Collection
    {
        $used = $onlyUsed ? $product->variants->flatMap(fn (Variant $variant) => $variant->optionValues)->pluck('id')->unique()->all() : null;

        return $product->optionValues->map(fn (ProductOptionValue $row): OptionValue => $row->value)
            ->filter(fn (OptionValue $value): bool => $value->option_type_id === $axis->option_type_id && ($used === null || in_array($value->id, $used, true)))->values();
    }

    /**
     * @return Collection<int, Variant>
     */
    private function variantsWith(Product $product, int $valueId): Collection
    {
        return $product->variants->filter(fn (Variant $variant): bool => $variant->optionValues->contains('id', $valueId))->values();
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
    private function variantModel(Product $product, string $locale): array
    {
        $axes = $product->options;
        $types = $axes->mapWithKeys(fn (ProductOption $axis): array => [$axis->option_type_id => $axis->type->code]);
        $positions = $product->optionValues->mapWithKeys(fn (ProductOptionValue $row): array => [$row->option_value_id => $row->position]);
        $ordered = fn (Variant $variant): array => $axes->map(fn (ProductOption $axis): ?OptionValue => $variant->optionValues->first(fn (OptionValue $value): bool => $value->option_type_id === $axis->option_type_id))->filter()->values()->all();
        $variants = $product->variants->sortBy(fn (Variant $variant): string => implode('-', array_map(fn (OptionValue $value): string => sprintf('%05d', $positions[$value->id] ?? 99999), $ordered($variant))))->values();

        $gallery = [];
        foreach ($variants as $variant) {
            foreach ($variant->gallery as $image) {
                // Video Thư viện trong gallery (L24): `url` là file gốc, `poster_url`/`thumb_url` từ ảnh poster (có thể null).
                $gallery[$image->media_id] ??= $image->media->isVideo() ? [
                    'id' => $image->media_id, 'kind' => 'video', 'url' => $image->media->url(), 'srcset' => null,
                    'poster_url' => $image->media->posterUrl(800, ImageSizes::PRODUCT), 'thumb_url' => $image->media->posterUrl(160, ImageSizes::PRODUCT), 'alt' => $image->alt,
                ] : [
                    'id' => $image->media_id, 'kind' => 'image', 'url' => $image->media->url(800, ImageSizes::PRODUCT), 'srcset' => $image->media->srcset(ImageSizes::PRODUCT) ?: null,
                    'poster_url' => null, 'thumb_url' => $image->media->url(160, ImageSizes::PRODUCT), 'alt' => $image->alt,
                ];
            }
        }

        return [
            'options' => $axes->map(fn (ProductOption $axis): array => [
                'code' => $axis->type->code, 'name' => $axis->type->translate('name', $locale) ?? $axis->type->code, 'display_type' => $axis->type->display_type->value,
                'values' => $this->axisValues($product, $axis)->map(fn (OptionValue $value): array => [
                    'id' => $value->id, 'code' => $value->code, 'name' => $value->translate('name', $locale) ?? $value->code,
                    'hex' => $value->hex, 'family' => $value->family, 'group' => $value->group,
                    'image_url' => self::imageUrl($this->variantsWith($product, $value->id)->flatMap(fn (Variant $variant) => $variant->gallery), 400),
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
     * @param  Collection<int, ProductAttributeValue>  $rows
     * @return list<array{code: string, name: string|null, value: string|bool|list<string|null>|null}>
     */
    private function attributes(Collection $rows, string $locale): array
    {
        return $rows
            ->filter(fn (ProductAttributeValue $row): bool => $row->attribute->kind === AttributeKind::Spec)
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

    /**
     * Ảnh đầu tiên của các mục gallery (bỏ qua video), không có thì poster của video đầu tiên có poster (L24).
     *
     * @param  iterable<Mediable>  $items
     */
    private static function imageUrl(iterable $items, int $size): ?string
    {
        $items = collect($items);

        return $items->first(fn (Mediable $item): bool => ! $item->media->isVideo())?->media->url($size, ImageSizes::PRODUCT)
            ?? $items->map(fn (Mediable $item): ?string => $item->media->posterUrl($size, ImageSizes::PRODUCT))->filter()->first();
    }
}
