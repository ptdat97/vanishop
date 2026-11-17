{{-- Header gọn: tên cửa hàng ở giữa, nút đóng bên phải, ô tìm kiếm bên dưới. $id/$title từ theme::partials.drawer. --}}
<div class="shrink-0 bg-white px-3 pt-[env(safe-area-inset-top)]">
    <h2 id="{{ $id }}-title" class="sr-only">{{ $title }}</h2>
    <div class="relative flex h-11 items-center justify-center">
        <a href="{{ vani_route('storefront.home') }}" class="max-w-[60%] truncate text-[15px] font-semibold text-gray-900">{{ config('app.name') }}</a>
        <button type="button" data-vani-drawer-close aria-label="{{ __('Đóng') }}"
            class="absolute right-0 inline-flex h-8 min-w-12 items-center justify-center rounded-full border border-gray-200 px-3 text-xl leading-none text-gray-700 active:bg-gray-100 focus-visible:outline-2 focus-visible:outline-primary">×</button>
    </div>
    <form action="{{ vani_route('storefront.search') }}" method="get" role="search" class="pb-2.5">
        <label for="vani-category-nav-q" class="sr-only">{{ __('Tìm kiếm') }}</label>
        <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
            {{-- 16px: iOS không phóng to khi focus. --}}
            <input id="vani-category-nav-q" name="q" type="search" enterkeyhint="search" placeholder="{{ __('Tìm sản phẩm…') }}"
                class="h-8 w-full rounded-md bg-gray-100 px-9 text-center text-base text-gray-900 outline-none placeholder:text-sm placeholder:text-gray-400 focus:ring-1 focus:ring-gray-300">
        </div>
    </form>
</div>
