<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

use Illuminate\Support\Facades\App;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Contracts\Data\LinkTarget;
use Modules\Storefront\Contracts\LinkResolver;

/**
 * Breadcrumb của trang storefront (§3.16-6) dựng sẵn cho theme: thêm "Trang chủ" ở đầu, phân giải link có kiểu
 * (`LinkResolver`; đối tượng đã ẩn/xoá → chữ thường), mục cuối là trang hiện tại (không link), kèm JSON-LD `BreadcrumbList`. Partial `theme::partials.breadcrumb`
 * nhận `$crumbs`; view composer gắn `$breadcrumb` = kết quả của {@see self::trail()} nên partial chỉ hiển thị.
 */
final class Breadcrumbs
{
    public function __construct(private readonly LinkResolver $links) {}

    /**
     * @param  list<array{label: string, url?: string|null, link?: mixed}>  $crumbs
     * @return array{items: list<array{label: string, url: string|null, current: bool}>, json_ld: string}
     */
    public function trail(array $crumbs): array
    {
        $items = [['label' => __('storefront::labels.home'), 'url' => LocalizedUrls::route('storefront.home')]];
        foreach ($crumbs as $crumb) {
            $items[] = ['label' => (string) $crumb['label'], 'url' => $crumb['url'] ?? (isset($crumb['link']) ? $this->resolve($crumb['link']) : null)];
        }
        $items[array_key_last($items)]['url'] = null;
        $last = array_key_last($items);

        return [
            'items' => array_map(fn (int $index, array $item): array => [...$item, 'current' => $index === $last], array_keys($items), $items),
            'json_ld' => StructuredData::breadcrumbs($items),
        ];
    }

    /**
     * Đường danh mục từ gốc (`CatalogReader` — `breadcrumb` của sản phẩm/danh mục) → `$crumbs`.
     *
     * @param  list<array{name: string, slug: string}>  $categories
     * @return list<array{label: string, url: string}>
     */
    public static function categories(array $categories): array
    {
        return array_map(fn (array $category): array => ['label' => (string) $category['name'], 'url' => LocalizedUrls::route('storefront.category', $category['slug'])], $categories);
    }

    private function resolve(mixed $link): ?string
    {
        $target = $link instanceof LinkTarget ? $link : (is_array($link) ? LinkTarget::fromArray($link) : null);
        if ($target === null) {
            return null;
        }
        $resolved = $this->links->resolve($target, App::getLocale());

        return $resolved->exists ? $resolved->url : null;
    }
}
