{{-- data-vani-listing (0.3.93): app.js thay vùng này khi lọc/phân trang (không tải lại trang); không JS là link thường.
     $listingView: link lọc/sắp xếp/phân trang Core dựng sẵn (Storefront\View\Composers\ListingComposer) — partial chỉ in ra. --}}
<div data-vani-listing>
<x-vani::hook-slot name="vani.storefront.plp.before_listing" :args="[$listingView['context'], $listing]" />
<div class="grid gap-8 md:grid-cols-[14rem_1fr]">
    <aside class="text-sm" aria-label="{{ __('theme::catalog.filters') }}">
        <details class="group md:contents" data-vani-filter-disclosure>
            <summary class="flex cursor-pointer list-none items-center justify-between rounded-[var(--radius-theme)] border border-slate-200 px-4 py-3 font-semibold marker:hidden md:hidden">
                <span>{{ __('theme::catalog.filters') }}</span>
                <svg viewBox="0 0 20 20" class="size-4 transition-transform group-open:rotate-180" fill="none" aria-hidden="true"><path d="m4 7 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </summary>
            <div class="space-y-6 pt-4 md:block md:pt-0">
        {{-- Bộ lọc theo cấu hình Admin → Catalog → Bộ lọc (thứ tự, bật/tắt, kiểu hiển thị); link thường, chạy khi tắt JS. --}}
        @foreach ($listingView['filters'] as $filter)
            @if ($filter['display'] === 'range')
                {{-- Khoảng giá niêm yết: form GET thường (chạy khi tắt JS), giữ các bộ lọc đang chọn. --}}
                <section data-filter="{{ $filter['key'] }}">
                    <h2 class="mb-2 font-semibold">{{ $filter['label'] }}</h2>
                    <form method="get" action="{{ $listingView['action'] }}" class="space-y-2">
                        @foreach ($filter['range']['hidden_fields'] as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <div class="flex items-center gap-2">
                            <input type="number" name="price_min" min="0" step="1000" inputmode="numeric" value="{{ $filter['range']['min_value'] }}" placeholder="{{ $filter['range']['min_placeholder'] }}"
                                aria-label="{{ __('theme::catalog.price_from') }}" class="w-full min-w-0 rounded-[var(--radius-theme)] border border-slate-300 px-2 py-1.5">
                            <span aria-hidden="true">–</span>
                            <input type="number" name="price_max" min="0" step="1000" inputmode="numeric" value="{{ $filter['range']['max_value'] }}" placeholder="{{ $filter['range']['max_placeholder'] }}"
                                aria-label="{{ __('theme::catalog.price_to') }}" class="w-full min-w-0 rounded-[var(--radius-theme)] border border-slate-300 px-2 py-1.5">
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="rounded-[var(--radius-theme)] border border-slate-900 px-3 py-1.5 font-medium">{{ __('theme::common.apply') }}</button>
                            @if ($filter['range']['clear_url'])
                                <a href="{{ $filter['range']['clear_url'] }}" class="text-slate-500 underline">{{ __('theme::catalog.clear_price_filter') }}</a>
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
                        <li>
                            <a href="{{ $option['url'] }}" @if ($option['active']) aria-current="true" @endif
                               @class(['font-semibold text-primary' => $option['active'] && $filter['display'] !== 'swatch'])
                               @if ($filter['display'] === 'swatch') title="{{ $option['label'] }} ({{ $option['count'] }})" @endif>
                                @if ($filter['display'] === 'swatch')
                                    <span @class(['block h-7 w-7 rounded-full border', 'ring-2 ring-primary ring-offset-1' => $option['active'], 'border-slate-300' => ! $option['active']]) style="background: {{ $option['swatch'] ?? '#e5e7eb' }}"></span>
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
            </div>
        </details>
    </aside>

    <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm">
            <p class="text-slate-500">{{ trans_choice('theme::catalog.product_count', $listing['total'], ['count' => $listing['total']]) }}</p>
            {{-- Sắp xếp: form GET thường, giữ bộ lọc đang chọn; sắp theo giá ẩn khi cửa hàng ẩn giá với khách chưa đăng nhập. --}}
            <form method="get" action="{{ $listingView['action'] }}" class="flex items-center gap-2" data-vani-sort>
                @foreach ($listingView['sort']['hidden_fields'] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <label for="vani-listing-sort" class="text-slate-500">{{ __('theme::catalog.sort_by') }}</label>
                <select id="vani-listing-sort" name="sort" class="rounded-[var(--radius-theme)] border border-slate-300 px-2 py-1.5" onchange="this.form.submit()">
                    <option value="newest" @selected($listingView['sort']['current'] === 'newest')>{{ __('theme::catalog.newest') }}</option>
                    @if ($listingView['sort']['by_price'])
                        <option value="price_asc" @selected($listingView['sort']['current'] === 'price_asc')>{{ __('theme::catalog.price_low_high') }}</option>
                        <option value="price_desc" @selected($listingView['sort']['current'] === 'price_desc')>{{ __('theme::catalog.price_high_low') }}</option>
                    @endif
                </select>
                <noscript><button type="submit" class="underline">{{ __('theme::common.apply') }}</button></noscript>
            </form>
        </div>
        @if ($listing['items'] === [])
            <p class="py-12 text-center text-slate-500">{{ __('theme::catalog.no_matching_products') }}</p>
            <x-vani::hook-slot name="vani.storefront.plp.empty" :args="[$listingView['context'], $listing]" />
        @else
            {{-- Lưới 3 cột cạnh cột lọc 14rem: vùng chứa ≈ 1152 − 224 − 32 px. --}}
            @include('theme::partials.product-grid', ['products' => $listing['items'], 'gridClass' => 'grid grid-cols-2 gap-6 md:grid-cols-3', 'layout' => [896, 3, 2], 'stagger' => true, 'eager' => 2])
        @endif

        @if ($listingView['pages'] !== [])
            <nav class="mt-8 flex gap-2 text-sm" aria-label="{{ __('theme::common.pagination') }}">
                @foreach ($listingView['pages'] as $page)
                    @if ($page['current'])
                        <span aria-current="page" class="rounded border border-primary px-3 py-1 font-semibold">{{ $page['number'] }}</span>
                    @else
                        <a href="{{ $page['url'] }}" class="rounded border border-slate-200 px-3 py-1">{{ $page['number'] }}</a>
                    @endif
                @endforeach
            </nav>
        @endif
    </section>
</div>
    <x-vani::hook-slot name="vani.storefront.plp.after_listing" :args="[$listingView['context'], $listing]" />
    </div>
