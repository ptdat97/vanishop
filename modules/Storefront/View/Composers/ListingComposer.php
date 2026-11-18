<?php

declare(strict_types=1);

namespace Modules\Storefront\View\Composers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Storefront\View\ListingUrl;

/**
 * Dữ liệu dựng sẵn cho `theme::partials.listing` (`$listingView`) từ `$listing` (ProductViews::listing) và query hiện tại:
 * link bật/tắt từng lựa chọn lọc + trạng thái đang chọn, form khoảng giá (ô ẩn giữ bộ lọc khác, giá trị, gợi ý, link bỏ lọc),
 * sắp xếp, phân trang, ngữ cảnh trang. Bộ lọc thương hiệu ẩn trên trang thương hiệu. Partial chỉ in ra.
 */
final class ListingComposer
{
    private const PRICE_PARAMS = ['price_min', 'price_max'];

    public function __construct(
        private readonly Request $request,
        private readonly MoneyFormatter $money,
    ) {}

    public function compose(View $view): void
    {
        $data = $view->getData();
        $listing = (array) ($data['listing'] ?? []);
        $view->with('listingView', [
            // $listingContext: trang gọi truyền vào (danh mục / thương hiệu); mặc định là trang tìm kiếm.
            'context' => $data['listingContext'] ?? ['type' => 'search', 'category' => null, 'brand' => null],
            'action' => $this->request->url(),
            'filters' => $this->filters((array) ($listing['filters'] ?? [])),
            'sort' => $this->sort((bool) ($listing['sortable_by_price'] ?? false)),
            'pages' => $this->pages((int) ($listing['total'] ?? 0), (int) ($listing['per_page'] ?? 1), (int) ($listing['page'] ?? 1)),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     * @return list<array<string, mixed>>
     */
    private function filters(array $filters): array
    {
        $onBrandPage = $this->request->routeIs('storefront.brand', 'storefront.*.brand');
        $result = [];
        foreach ($filters as $filter) {
            if ($filter['key'] === 'brand' && $onBrandPage) {
                continue;
            }
            if ($filter['display'] === 'range') {
                $currency = (string) config('vanishop.currency');
                $filtered = $this->request->filled('price_min') || $this->request->filled('price_max');
                $rest = http_build_query(Arr::except($this->request->query(), [...self::PRICE_PARAMS, 'page']));
                $result[] = [...$filter, 'range' => [
                    ...$filter['range'],
                    'hidden_fields' => ListingUrl::hiddenFields($this->request, self::PRICE_PARAMS),
                    'min_value' => $this->request->query('price_min'), 'max_value' => $this->request->query('price_max'),
                    'min_placeholder' => $this->money->formatAmount((int) $filter['range']['min'], $currency),
                    'max_placeholder' => $this->money->formatAmount((int) $filter['range']['max'], $currency),
                    'clear_url' => $filtered ? $this->request->url().($rest === '' ? '' : '?'.$rest) : null,
                ]];

                continue;
            }
            $result[] = [...$filter, 'options' => array_map(fn (array $option): array => [
                ...$option,
                'url' => ListingUrl::toggle($this->request, $filter['param'], (string) $option['value']),
                'active' => ListingUrl::active($this->request, $filter['param'], (string) $option['value']),
            ], $filter['options'])];
        }

        return $result;
    }

    /**
     * Sắp theo giá ẩn khi cửa hàng ẩn giá với khách chưa đăng nhập.
     *
     * @return array{hidden_fields: array<string, string>, current: string, by_price: bool}
     */
    private function sort(bool $byPrice): array
    {
        $current = $this->request->query('sort');

        return ['hidden_fields' => ListingUrl::hiddenFields($this->request, ['sort']), 'current' => is_string($current) && $current !== '' ? $current : 'newest', 'by_price' => $byPrice];
    }

    /**
     * @return list<array{number: int, url: string|null, current: bool}> rỗng khi chỉ một trang
     */
    private function pages(int $total, int $perPage, int $page): array
    {
        $count = (int) ceil($total / max(1, $perPage));
        if ($count <= 1) {
            return [];
        }

        return array_map(fn (int $number): array => ['number' => $number, 'current' => $number === $page, 'url' => $number === $page ? null : ListingUrl::page($this->request, $number)], range(1, $count));
    }
}
