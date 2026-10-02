@php($facets = $listing['facets'])
@php($pages = (int) ceil($listing['total'] / max(1, $listing['per_page'])))
<div class="grid gap-8 md:grid-cols-[14rem_1fr]">
    <aside class="space-y-6 text-sm" aria-label="Bộ lọc">
        @if (! empty($facets['brands']) && ! request()->routeIs('storefront.brand'))
            <section>
                <h2 class="mb-2 font-semibold">Thương hiệu</h2>
                <ul class="space-y-1">
                    @foreach ($facets['brands'] as $brand)
                        <li>
                            <a href="{{ \Modules\Storefront\View\ListingUrl::toggle(request(), 'brand', $brand['slug']) }}"
                               @class(['font-semibold text-primary' => \Modules\Storefront\View\ListingUrl::active(request(), 'brand', $brand['slug'])])>
                                {{ $brand['name'] }} <span class="text-slate-400">({{ $brand['count'] }})</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if (! empty($facets['color_families']))
            <section>
                <h2 class="mb-2 font-semibold">Màu</h2>
                <ul class="space-y-1">
                    @foreach ($facets['color_families'] as $family => $count)
                        <li>
                            <a href="{{ \Modules\Storefront\View\ListingUrl::toggle(request(), 'color', $family) }}"
                               @class(['font-semibold text-primary' => \Modules\Storefront\View\ListingUrl::active(request(), 'color', $family)])>
                                {{ $family }} <span class="text-slate-400">({{ $count }})</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        <x-vani::hook-slot name="vani.storefront.plp.filters" :args="[$listing]" />
    </aside>

    <section>
        <p class="mb-4 text-sm text-slate-500">{{ $listing['total'] }} sản phẩm</p>
        @if ($listing['items'] === [])
            <p class="py-12 text-center text-slate-500">Không có sản phẩm phù hợp.</p>
        @else
            <div class="grid grid-cols-2 gap-6 md:grid-cols-3">
                @foreach ($listing['items'] as $product)
                    @include('theme::partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif

        @if ($pages > 1)
            <nav class="mt-8 flex gap-2 text-sm" aria-label="Phân trang">
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
