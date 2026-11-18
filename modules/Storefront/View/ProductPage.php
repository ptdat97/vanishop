<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

use Modules\Catalog\Contracts\MediaUrls;

/**
 * Dữ liệu hiển thị của trang sản phẩm dựng sẵn cho theme (`$page` của `theme::pages.product`) từ chi tiết sản phẩm
 * (`ProductViews::detail`): theme chỉ in ra, không tự tính. Gồm metadata (tiêu đề, mô tả, JSON-LD — có giá trị dự phòng),
 * breadcrumb, gallery (khung theo tỉ lệ ảnh sản phẩm, ảnh phóng to = bản lớn nhất trong srcset), bộ chọn biến thể
 * ({@see VariantSelector}, kèm tên/giá trị ô nhập, giá từng SKU dạng HTML) và CSS ẩn/hiện nhóm SKU theo trục đầu.
 * Plugin đổi được qua hook `vani.storefront.view.pages.product` (cả `product` lẫn `page`).
 */
final class ProductPage
{
    /** Bề rộng ảnh phóng to khi ảnh không có srcset. */
    private const ZOOM_FALLBACK_WIDTH = 800;

    /** `sizes` của ảnh gallery theo bố cục 2 cột của PDP (khung 1152px). */
    private const GALLERY_SIZES = '(min-width: 1152px) 560px, (min-width: 768px) 50vw, 100vw';

    public function __construct(private readonly MediaUrls $media) {}

    /**
     * @param  array<string, mixed>  $product
     * @return array{
     *     meta: array{title: string, description: string|null, image: string|null, json_ld: list<string>},
     *     crumbs: list<array{label: string, url?: string}>,
     *     gallery: array<string, mixed>,
     *     selector: array<string, mixed>,
     *     variant_css: string|null,
     *     can_buy: bool
     * }
     */
    public function for(array $product, ?string $requestedSku = null): array
    {
        // "Giữ tỉ lệ gốc" → khung 3:4 mặc định của theme (như `vani_product_ratio()`).
        [$ratioW, $ratioH] = $this->media->productRatio() ?? [3, 4];

        return [
            'meta' => self::meta($product),
            'crumbs' => [...Breadcrumbs::categories((array) ($product['breadcrumb'] ?? [])), ['label' => (string) $product['name']]],
            'gallery' => self::gallery($product, $ratioW, $ratioH),
            'selector' => $selector = $this->selector($product, $requestedSku),
            'variant_css' => self::variantCss($selector),
            'can_buy' => (bool) ($product['purchase']['is_purchasable'] ?? false),
        ];
    }

    /**
     * Tiêu đề: `meta_title` → tên. Mô tả: `meta_description` → đoạn đầu mô tả (≤ 160 ký tự) → null.
     *
     * @param  array<string, mixed>  $product
     * @return array{title: string, description: string|null, image: string|null, json_ld: list<string>}
     */
    private static function meta(array $product): array
    {
        $image = PageMeta::absoluteUrl($product['media']['gallery'][0]['url'] ?? $product['image_url'] ?? null);

        return [
            'title' => PageMeta::title($product['meta_title'] ?? null, (string) $product['name']),
            'description' => PageMeta::description($product['meta_description'] ?? null, $product['description'] ?? null),
            'image' => $image,
            'json_ld' => [StructuredData::product([...$product, 'image_url' => $image])],
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array{aspect: string, label: string, slides: list<array<string, mixed>>, thumbs: list<array<string, mixed>>, navigation: bool}
     */
    private static function gallery(array $product, int $ratioW, int $ratioH): array
    {
        $name = (string) $product['name'];
        $images = array_values((array) ($product['media']['gallery'] ?? []));
        $fallback = $product['image_url'] ?? null;
        $sources = $images !== [] ? $images : ($fallback !== null ? [['url' => $fallback, 'srcset' => null, 'alt' => $name]] : []);
        $height = fn (int $width): int => (int) round($width * $ratioH / $ratioW);

        $slides = array_map(function (int $index, array $image) use ($name, $height): array {
            [$zoomUrl, $zoomWidth] = self::largest($image);

            return [
                'url' => (string) $image['url'], 'srcset' => $image['srcset'] ?? null, 'sizes' => ($image['srcset'] ?? null) ? self::GALLERY_SIZES : null,
                'alt' => (string) ($image['alt'] ?? $name), 'width' => 600, 'height' => $height(600),
                // Ảnh đầu là LCP: tải ngay, ưu tiên cao; ảnh sau tải lười.
                'main' => $index === 0,
                'zoom' => ['url' => $zoomUrl, 'width' => $zoomWidth, 'height' => $height($zoomWidth)],
                'zoom_label' => __('Phóng to ảnh :index của :name', ['index' => $index + 1, 'name' => $name]),
            ];
        }, array_keys($sources), $sources);

        $thumbs = count($images) > 1 ? array_map(fn (int $index, array $image): array => [
            'index' => $index, 'url' => (string) $image['url'], 'thumb_url' => (string) ($image['thumb_url'] ?? $image['url']),
            'width' => 150, 'height' => $height(150), 'active' => $index === 0,
            'label' => __('Ảnh :index của :name', ['index' => $index + 1, 'name' => $name]),
        ], array_keys($images), $images) : [];

        return [
            'aspect' => "{$ratioW} / {$ratioH}",
            'label' => __('Ảnh sản phẩm :name', ['name' => $name]),
            'slides' => $slides,
            'thumbs' => $thumbs,
            'navigation' => count($slides) > 1,
        ];
    }

    /**
     * Bản lớn nhất trong srcset ("url 480w, url 960w") — ảnh phóng to; không có srcset → ảnh chính, rộng mặc định.
     *
     * @param  array<string, mixed>  $image
     * @return array{0: string, 1: int}
     */
    private static function largest(array $image): array
    {
        $best = [(string) $image['url'], self::ZOOM_FALLBACK_WIDTH];
        foreach (explode(',', (string) ($image['srcset'] ?? '')) as $entry) {
            [$source, $width] = array_pad(preg_split('/\s+/', trim($entry)) ?: [], 2, '');
            if ($source !== '' && (int) $width > $best[1]) {
                $best = [$source, (int) $width];
            }
        }

        return $best;
    }

    /**
     * Bộ chọn của {@see VariantSelector} kèm những gì theme cần in: tên/giá trị ô nhập của trục đầu, SKU bị khoá, giá từng
     * SKU dạng HTML (partial `theme::partials.variant-price`, JS thay vào chỗ giá) và câu nhắc khi chưa chọn.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function selector(array $product, ?string $requestedSku): array
    {
        $selector = VariantSelector::for($product, $requestedSku);
        $selector['choose_message'] = $selector['rest_names'] !== []
            ? __('storefront::messages.choose_option', ['name' => implode(', ', $selector['rest_names'])])
            : __('storefront::messages.choose_variant');
        $selector['rest_label'] = implode(' / ', $selector['rest_names']);
        $withPrice = fn (array $variant): array => [...$variant, 'price_html' => $variant['price'] ? view('theme::partials.variant-price', ['price' => $variant['price']])->render() : null];

        $primary = $selector['primary'];
        if ($primary !== null) {
            $single = $primary['single'];
            $primary['input_name'] = $single ? 'variant_id' : "axis[{$primary['code']}]";
            $primary['values'] = array_map(function (array $value) use ($single, $withPrice): array {
                if ($single) {
                    $value['variant'] = $withPrice($value['variant']);
                }
                $disabled = $single && $value['variant']['disabled'];

                return [
                    ...$value,
                    'input_value' => $single ? (string) $value['variant']['id'] : (string) $value['code'],
                    'disabled' => $disabled,
                    'checked' => $value['checked'] && ! $disabled,
                    'images' => $single ? $value['variant']['images'] : $value['images'],
                    'price_html' => $single ? $value['variant']['price_html'] : null,
                    'low_stock' => $single && $value['variant']['availability'] === 'low_stock',
                ];
            }, $primary['values']);
            $selector['primary'] = $primary;
        }
        $selector['groups'] = array_map(fn (array $variants): array => array_map(fn (array $variant): array => [
            ...$withPrice($variant), 'checked' => $variant['checked'] && ! $variant['disabled'],
        ], $variants), $selector['groups']);

        return $selector;
    }

    /**
     * Chỉ nhóm SKU / nhãn của giá trị trục đầu đang chọn hiện ra — CSS `:has()`, không cần JS. Giá trị nằm trong chuỗi
     * CSS đã thoát (mọi ký tự ngoài chữ/số thành `\HEX `), in thô trong `<style>` được.
     *
     * @param  array<string, mixed>  $selector
     */
    private static function variantCss(array $selector): ?string
    {
        $primary = $selector['primary'];
        if ($primary === null || $primary['values'] === []) {
            return null;
        }
        $css = '[data-variant-form] [data-option-group], [data-variant-form] [data-option-label] { display: none; }';
        foreach ($primary['values'] as $value) {
            $code = self::cssString((string) $value['code']);
            $checked = "[data-variant-form]:has([data-primary][data-option-value={$code}]:checked)";
            $css .= "\n{$checked} [data-option-group={$code}] { display: block; }\n{$checked} [data-option-label={$code}] { display: inline; }";
        }

        return $css;
    }

    private static function cssString(string $value): string
    {
        return '"'.preg_replace_callback('/[^A-Za-z0-9_-]/u', fn (array $match): string => '\\'.dechex((int) mb_ord($match[0])).' ', $value).'"';
    }
}
