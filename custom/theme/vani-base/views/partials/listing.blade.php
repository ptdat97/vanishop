{{-- data-vani-listing (0.3.93): app.js thay vùng này khi lọc/phân trang (không tải lại trang); link thường cho SEO.
     $listingView: link lọc/sắp xếp/phân trang Core dựng sẵn (Storefront\View\Composers\ListingComposer) — partial chỉ in ra. --}}
<p class="sr-only" role="status" data-vani-listing-status></p>
<div class="scroll-mt-24" tabindex="-1" data-vani-listing>
    <x-vani::hook-slot name="vani.storefront.plp.before_listing" :args="[$listingView['context'], $listing]" />
    @include('theme::partials.drawer', [
        'id' => 'vani-listing-filters',
        'title' => __('theme::catalog.filters'),
        'contentView' => 'theme::partials.listing-filters',
        'closeAttributes' => ['commandfor' => 'vani-listing-filters', 'command' => 'close'],
    ])

    <section>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-y border-border py-2 text-sm">
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-[var(--radius-theme)] border border-border px-3 font-medium"
                        data-vani-drawer-open="vani-listing-filters" aria-haspopup="dialog" aria-controls="vani-listing-filters"
                        commandfor="vani-listing-filters" command="show-modal">
                    {{ __('theme::catalog.filters') }}
                    @if ($listingView['active_filters'] !== [])
                        <span class="rounded-full bg-primary px-1.5 text-xs text-white">{{ count($listingView['active_filters']) }}</span>
                    @endif
                </button>
                <p class="text-slate-500" data-vani-listing-count>{{ trans_choice('theme::catalog.product_count', $listing['total'], ['count' => $listing['total']]) }}</p>
            </div>
            {{-- Sắp xếp: form GET thường, giữ bộ lọc đang chọn; sắp theo giá ẩn khi cửa hàng ẩn giá với khách chưa đăng nhập. --}}
            <form method="get" action="{{ $listingView['action'] }}" class="flex items-center gap-2" data-vani-sort>
                @foreach ($listingView['sort']['hidden_fields'] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <label for="vani-listing-sort" class="text-slate-500">{{ __('theme::catalog.sort_by') }}</label>
                <select id="vani-listing-sort" name="sort" class="min-h-11 max-w-full rounded-[var(--radius-theme)] border border-border bg-surface px-2 text-primary focus:border-primary">
                    <option value="newest" @selected($listingView['sort']['current'] === 'newest')>{{ __('theme::catalog.newest') }}</option>
                    @if ($listingView['sort']['by_price'])
                        <option value="price_asc" @selected($listingView['sort']['current'] === 'price_asc')>{{ __('theme::catalog.price_low_high') }}</option>
                        <option value="price_desc" @selected($listingView['sort']['current'] === 'price_desc')>{{ __('theme::catalog.price_high_low') }}</option>
                    @endif
                </select>
                <noscript><button type="submit" class="rounded border px-3 py-1.5">{{ __('theme::common.apply') }}</button></noscript>
            </form>
        </div>
        @if ($listingView['active_filters'] !== [] || $listingView['clear_filters_url'])
            <div class="mb-5 flex flex-wrap items-center gap-2 text-sm">
                @foreach ($listingView['active_filters'] as $chip)
                    <a href="{{ $chip['url'] }}" aria-label="{{ __('theme::catalog.remove_filter', ['filter' => $chip['label']]) }}" class="inline-flex min-h-11 max-w-full items-center gap-2 break-words border border-border bg-subtle px-3">
                        {{ $chip['label'] }} <span aria-hidden="true">×</span>
                    </a>
                @endforeach
                @if ($listingView['clear_filters_url'])
                    <a class="vani-text-link" href="{{ $listingView['clear_filters_url'] }}">{{ __('theme::catalog.clear_filters') }}</a>
                @endif
            </div>
        @endif
        @if ($listing['items'] === [])
            @include('theme::partials.empty-state', [
                'emptyMessage' => __('theme::catalog.no_matching_products'),
                'emptyClass' => 'bg-subtle px-4 py-12 text-center text-slate-500',
            ])
            <x-vani::hook-slot name="vani.storefront.plp.empty" :args="[$listingView['context'], $listing]" />
        @else
            @include('theme::partials.product-grid', ['products' => $listing['items'], 'stagger' => true, 'eager' => 2])
        @endif

        @if ($listingView['pages'] !== [])
            <nav class="mt-8 flex flex-wrap gap-2 text-sm" aria-label="{{ __('theme::common.pagination') }}" data-vani-pagination>
                @foreach ($listingView['pages'] as $page)
                    @if ($page['current'])
                        <span aria-current="page" class="inline-grid min-h-11 min-w-11 place-items-center rounded border border-primary font-semibold">{{ $page['number'] }}</span>
                    @else
                        <a href="{{ $page['url'] }}" class="inline-grid min-h-11 min-w-11 place-items-center rounded border border-border">{{ $page['number'] }}</a>
                    @endif
                @endforeach
            </nav>
        @endif
    </section>
    <x-vani::hook-slot name="vani.storefront.plp.after_listing" :args="[$listingView['context'], $listing]" />
</div>
