{{-- CategorySidebar — danh mục cấp 1 (tablist dọc), cuộn độc lập. Skeleton hiện tới khi dữ liệu về. --}}
<div class="relative w-[72px] shrink-0 overflow-y-auto overscroll-contain bg-white [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-cnav-sidebar>
    <div role="tablist" aria-orientation="vertical" aria-label="{{ __('theme::common.categories') }}" data-cnav-tabs>
        <div data-cnav-skeleton aria-hidden="true">
            @foreach (range(1, 9) as $index)
                <span class="mx-auto my-[17px] block h-3.5 w-10 animate-pulse rounded bg-gray-100"></span>
            @endforeach
        </div>
    </div>
    @include('theme::partials.mobile-category-nav.sidebar-item')
</div>
