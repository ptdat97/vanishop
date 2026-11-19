{{--
    CategoryContent — cột phải, cuộn độc lập. JS dựng panel của từng danh mục cấp 1 khi được chọn (lần đầu) vào
    [data-cnav-panels]; skeleton / lỗi / trống là trạng thái của lần tải dữ liệu.
--}}
<div class="min-w-0 flex-1 overflow-y-auto overscroll-contain px-3 pb-6 pt-2" data-cnav-content>
    <div data-cnav-skeleton aria-busy="true" aria-label="{{ __('theme::common.loading') }}">
        <span class="mb-4 block aspect-[5/2] animate-pulse rounded bg-gray-100"></span>
        <span class="mx-auto mb-4 block h-3 w-24 animate-pulse rounded bg-gray-100"></span>
        <span class="grid grid-cols-3 gap-x-2 gap-y-5">
            @foreach (range(1, 9) as $index)
                <span class="flex flex-col items-center gap-2">
                    <span class="block aspect-square w-[72%] animate-pulse rounded bg-gray-100"></span>
                    <span class="block h-2.5 w-10 animate-pulse rounded bg-gray-100"></span>
                </span>
            @endforeach
        </span>
    </div>

    <div class="flex flex-col items-center py-16 text-center text-sm text-gray-500" data-cnav-error hidden>
        <p>{{ __('theme::catalog.could_not_load_categories') }}</p>
        <button type="button" class="mt-3 rounded-full border border-gray-300 px-4 py-1.5 text-xs text-gray-700 active:bg-gray-50" data-cnav-retry>{{ __('theme::catalog.try_again') }}</button>
    </div>
    <p class="py-16 text-center text-sm text-gray-500" data-cnav-empty-store hidden>{{ __('theme::catalog.store_no_categories') }}</p>

    <div data-cnav-panels></div>

    <x-vani::hook-slot name="vani.storefront.header.nav" class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-2 text-xs text-gray-600" />

    <template data-cnav-template="panel">
        <div role="tabpanel" tabindex="0" class="outline-none motion-safe:animate-fade-in"></div>
    </template>
    <template data-cnav-template="empty">
        <p class="py-12 text-center text-sm text-gray-500">{{ __('theme::catalog.no_subcategories') }}</p>
    </template>
    <template data-cnav-template="all">
        <p class="mt-1 text-center">
            <a class="inline-flex max-w-full items-center rounded-full border border-gray-200 px-4 py-1.5 text-xs text-gray-600 active:bg-gray-50" data-cnav-link>
                <span class="truncate">{!! __('theme::catalog.view_all_name', ['name' => '<span data-cnav-name></span>']) !!}</span>
            </a>
        </p>
    </template>
    @include('theme::partials.mobile-category-nav.banner')
    @include('theme::partials.mobile-category-nav.section')
    @include('theme::partials.mobile-category-nav.subcategory-item')
</div>
