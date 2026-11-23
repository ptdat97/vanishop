<div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain p-4 pb-[max(1rem,env(safe-area-inset-bottom))] text-sm">
    {{-- Bộ lọc theo cấu hình Admin → Catalog → Bộ lọc (thứ tự, bật/tắt, kiểu hiển thị); link thường (SEO). --}}
    @foreach ($listingView['filters'] as $filter)
        @if ($filter['display'] === 'range')
            {{-- Khoảng giá niêm yết: form GET, giữ các bộ lọc đang chọn. --}}
            <section data-filter="{{ $filter['key'] }}">
                <h2 class="mb-2 font-semibold">{{ $filter['label'] }}</h2>
                <form method="get" action="{{ $listingView['action'] }}" class="space-y-2" data-vani-listing-range>
                    @foreach ($filter['range']['hidden_fields'] as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <div class="flex items-center gap-2">
                        <input type="number" name="price_min" min="0" step="1000" inputmode="numeric" value="{{ $filter['range']['min_value'] }}" placeholder="{{ $filter['range']['min_placeholder'] }}"
                            aria-label="{{ __('theme::catalog.price_from') }}" class="min-h-11 w-full min-w-0 rounded-[var(--radius-theme)] border border-border px-2 text-base">
                        <span aria-hidden="true">–</span>
                        <input type="number" name="price_max" min="0" step="1000" inputmode="numeric" value="{{ $filter['range']['max_value'] }}" placeholder="{{ $filter['range']['max_placeholder'] }}"
                            aria-label="{{ __('theme::catalog.price_to') }}" class="min-h-11 w-full min-w-0 rounded-[var(--radius-theme)] border border-border px-2 text-base">
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="min-h-11 rounded-[var(--radius-theme)] border border-border px-3 font-medium">{{ __('theme::common.apply') }}</button>
                        @if ($filter['range']['clear_url'])
                            <a href="{{ $filter['range']['clear_url'] }}" class="vani-text-link text-accent">{{ __('theme::catalog.clear_price_filter') }}</a>
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
                           @class(['flex min-h-11 items-center gap-1', 'min-w-11 justify-center' => $filter['display'] === 'swatch', 'font-semibold text-primary' => $option['active'] && $filter['display'] !== 'swatch'])
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
