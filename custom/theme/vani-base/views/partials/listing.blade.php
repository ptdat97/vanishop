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
            @if ($filter['display'] === 'range')
                {{-- Khoảng giá niêm yết: form GET thường (chạy khi tắt JS), giữ các bộ lọc đang chọn. --}}
                <section data-filter="{{ $filter['key'] }}">
                    <h2 class="mb-2 font-semibold">{{ $filter['label'] }}</h2>
                    <form method="get" action="{{ url()->current() }}" class="space-y-2">
                        @foreach (\Modules\Storefront\View\ListingUrl::hiddenFields(request(), ['price_min', 'price_max']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <div class="flex items-center gap-2">
                            <input type="number" name="price_min" min="0" step="1000" inputmode="numeric" value="{{ request('price_min') }}" placeholder="{{ vani_money($filter['range']['min']) }}"
                                aria-label="{{ __('Giá từ') }}" class="w-full min-w-0 rounded-[var(--radius-theme)] border border-slate-300 px-2 py-1.5">
                            <span aria-hidden="true">–</span>
                            <input type="number" name="price_max" min="0" step="1000" inputmode="numeric" value="{{ request('price_max') }}" placeholder="{{ vani_money($filter['range']['max']) }}"
                                aria-label="{{ __('Giá đến') }}" class="w-full min-w-0 rounded-[var(--radius-theme)] border border-slate-300 px-2 py-1.5">
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="rounded-[var(--radius-theme)] border border-slate-900 px-3 py-1.5 font-medium">{{ __('Áp dụng') }}</button>
                            @if (request()->filled('price_min') || request()->filled('price_max'))
                                <a href="{{ url()->current() }}{{ ($rest = http_build_query(\Illuminate\Support\Arr::except(request()->query(), ['price_min', 'price_max', 'page']))) === '' ? '' : '?'.$rest }}" class="text-slate-500 underline">{{ __('Bỏ lọc giá') }}</a>
                            @endif
                        </div>
                    </form>
                </section>
                @continue
            @endif
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
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm">
            <p class="text-slate-500">{{ trans_choice(':count sản phẩm', $listing['total'], ['count' => $listing['total']]) }}</p>
            {{-- Sắp xếp: form GET thường, giữ bộ lọc đang chọn; sắp theo giá ẩn khi cửa hàng ẩn giá với khách chưa đăng nhập. --}}
            <form method="get" action="{{ url()->current() }}" class="flex items-center gap-2" data-vani-sort>
                @foreach (\Modules\Storefront\View\ListingUrl::hiddenFields(request(), ['sort']) as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <label for="vani-listing-sort" class="text-slate-500">{{ __('Sắp xếp') }}</label>
                <select id="vani-listing-sort" name="sort" class="rounded-[var(--radius-theme)] border border-slate-300 px-2 py-1.5" onchange="this.form.submit()">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>{{ __('Mới nhất') }}</option>
                    @if ($listing['sortable_by_price'] ?? false)
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('Giá thấp đến cao') }}</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('Giá cao đến thấp') }}</option>
                    @endif
                </select>
                <noscript><button type="submit" class="underline">{{ __('Áp dụng') }}</button></noscript>
            </form>
        </div>
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
