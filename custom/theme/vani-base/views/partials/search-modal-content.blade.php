<div class="p-4">
    <form action="{{ vani_route('storefront.search') }}" method="get" role="search" class="relative"
        data-vani-suggest="{{ route('api.storefront.v1.search.suggestions') }}" data-locale="{{ app()->getLocale() }}">
        <label for="vani-search-modal-q" class="sr-only">{{ __('Tìm kiếm') }}</label>
        <div class="flex gap-2">
            <input id="vani-search-modal-q" name="q" type="search" value="{{ request()->routeIs('storefront.search') ? request('q') : '' }}"
                placeholder="{{ __('Tìm sản phẩm…') }}" @if (($popularSearches ?? []) !== []) list="vani-search-popular-searches" @endif
                class="min-w-0 flex-1 rounded-[var(--radius-theme)] border border-slate-300 px-3 py-2 text-sm"
                autofocus>
            <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90">
                {{ __('Tìm kiếm') }}
            </button>
        </div>
        <div data-vani-suggest-box hidden class="absolute inset-x-0 top-full z-30 mt-1 rounded-[var(--radius-theme)] border border-slate-200 bg-white p-2 text-sm shadow-lg"></div>
        @if (($popularSearches ?? []) !== [])
            <datalist id="vani-search-popular-searches">
                @foreach ($popularSearches as $term)
                    <option value="{{ $term }}"></option>
                @endforeach
            </datalist>
        @endif
    </form>
</div>
