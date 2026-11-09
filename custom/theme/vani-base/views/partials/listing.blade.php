{{-- data-vani-listing (0.3.93): app.js thay vùng này khi lọc/phân trang (không tải lại trang); không JS là link thường. --}}
<div data-vani-listing>
@php($pages = (int) ceil($listing['total'] / max(1, $listing['per_page'])))
{{-- $listingContext: trang gọi truyền vào (danh mục / thương hiệu); mặc định là trang tìm kiếm. --}}
<x-vani::hook-slot name="vani.storefront.plp.before_listing" :args="[$listingContext ?? ['type' => 'search', 'category' => null, 'brand' => null], $listing]" />
<div class="grid gap-8 md:grid-cols-[14rem_1fr]">
    <aside class="space-y-6 text-sm" aria-label="{{ __('Bộ lọc') }}">
        {{-- Bộ lọc theo cấu hình Admin → Catalog → Bộ lọc (thứ tự, bật/tắt, kiểu hiển thị); link thường, chạy khi tắt JS. --}}
        @foreach ($listing['filters'] ?? [] as $filter)
            @continue($filter['key'] === 'brand' && request()->routeIs('storefront.brand', 'storefront.*.brand'))
            <section data-filter="{{ $filter['key'] }}">
                <h2 class="mb-2 font-semibold">{{ $filter['label'] }}</h2>
                <ul @class(['flex flex-wrap gap-2' => $filter['display'] === 'swatch', 'space-y-1' => $filter['display'] !== 'swatch'])>
                    @foreach ($filter['options'] as $option)
                        @php($active = \Modules\Storefront\View\ListingUrl::active(request(), $filter['param'], $option['value']))
                        <li>
                            <a href="{{ \Modules\Storefront\View\ListingUrl::toggle(request(), $filter['param'], $option['value']) }}" @if ($active) aria-current="true" @endif
                               @class(['font-semibold text-primary' => $active && $filter['display'] !== 'swatch'])
                               @if ($filter['display'] === 'swatch') title="{{ $option['label'] }} ({{ $option['count'] }})" @endif>
                                @if ($filter['display'] === 'swatch')
                                    <span @class(['block h-7 w-7 rounded-full border', 'ring-2 ring-primary ring-offset-1' => $active, 'border-slate-300' => ! $active]) style="background: {{ $option['swatch'] ?? '#e5e7eb' }}"></span>
                                    <span class="sr-only">{{ $option['label'] }} ({{ $option['count'] }})</span>
                                @else
                                    {{ $option['label'] }} <span class="text-slate-400">({{ $option['count'] }})</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
        <x-vani::hook-slot name="vani.storefront.plp.filters" :args="[$listing]" />
    </aside>

    <section>
        <p class="mb-4 text-sm text-slate-500">{{ trans_choice(':count sản phẩm', $listing['total'], ['count' => $listing['total']]) }}</p>
        @if ($listing['items'] === [])
            <p class="py-12 text-center text-slate-500">{{ __('Không có sản phẩm phù hợp.') }}</p>
            <x-vani::hook-slot name="vani.storefront.plp.empty" :args="[$listingContext ?? ['type' => 'search', 'category' => null, 'brand' => null], $listing]" />
        @else
            <div class="grid grid-cols-2 gap-6 md:grid-cols-3" data-vani-motion-stagger>
                @foreach ($listing['items'] as $product)
                    {{-- Lưới 3 cột cạnh cột lọc 14rem: vùng chứa ≈ 1152 − 224 − 32 px. --}}
                    @include('theme::partials.product-card', ['product' => $product, 'layout' => [896, 3, 2]])
                @endforeach
            </div>
        @endif

        @if ($pages > 1)
            <nav class="mt-8 flex gap-2 text-sm" aria-label="{{ __('Phân trang') }}">
                @for ($page = 1; $page <= $pages; $page++)
                    @if ($page === $listing['page'])
                        <span aria-current="page" class="rounded border border-primary px-3 py-1 font-semibold">{{ $page }}</span>
                    @else
                        <a href="{{ \Modules\Storefront\View\ListingUrl::page(request(), $page) }}" class="rounded border border-slate-200 px-3 py-1">{{ $page }}</a>
                    @endif
                @endfor
            </nav>
        @endif
    </section>
</div>
    <x-vani::hook-slot name="vani.storefront.plp.after_listing" :args="[$listingContext ?? ['type' => 'search', 'category' => null, 'brand' => null], $listing]" />
    </div>
