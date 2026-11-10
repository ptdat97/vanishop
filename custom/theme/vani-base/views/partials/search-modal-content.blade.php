
<div class="relative z-20 px-5 pb-6 pt-2 sm:px-7">

    <form
        action="{{ vani_route('storefront.search') }}"
        method="get"
        role="search"
        class="relative z-20"
        data-vani-suggest="{{ route('api.storefront.v1.search.suggestions') }}"
        data-locale="{{ app()->getLocale() }}"
    >
        <label for="vani-search-modal-q" class="sr-only">
            {{ __('Tìm kiếm') }}
        </label>

        <div class="flex items-center border-b-2 border-slate-200
                    transition-colors focus-within:border-slate-900">
            <input
                id="vani-search-modal-q"
                name="q"
                type="search"
                value="{{ request()->routeIs('storefront.search') ? request('q') : '' }}"
                placeholder="{{ __('Bạn đang tìm kiếm gì?') }}"
                class="min-w-0 flex-1 border-0 bg-transparent px-3 py-4
                       text-base outline-none ring-0 focus:ring-0"
                autocomplete="off"
            >

            <button type="submit" aria-label="{{ __('Tìm kiếm') }}"
                    class="flex size-10 items-center justify-center">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     class="size-5">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" d="m20 20-4-4"/>
                </svg>
            </button>
        </div>

        {{-- Suggestions --}}
        <div
            data-vani-suggest-box
            hidden
            class="relative mt-3 w-full
           rounded-xl border border-slate-200
           bg-white p-2 text-sm"
        ></div>
    </form>

    {{-- Popular searches --}}
    @if (($popularSearches ?? []) !== [])
        <section class="mt-8">
            <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider">
                {{ __('Tìm kiếm phổ biến') }}
            </h3>

            <div class="flex flex-wrap gap-2">
                @foreach ($popularSearches as $term)
                    <a href="{{ vani_route('storefront.search', ['q' => $term]) }}"
                       class="rounded-full border border-slate-200 px-4 py-2
                              text-sm text-slate-600 transition
                              hover:border-slate-900 hover:bg-slate-900 hover:text-white">
                        {{ $term }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

</div>
