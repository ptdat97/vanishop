{{-- Link tới danh sách yêu thích + số món (JS điền). Chưa đăng nhập → trang đăng nhập, rồi quay lại danh sách. --}}
<a href="{{ vani_route('storefront.p.vani-wishlist.account.index') }}"
    class="relative inline-flex size-10 items-center justify-center rounded text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
    data-vani-wishlist-link aria-label="{{ __('Yêu thích') }}" title="{{ __('Yêu thích') }}">
    <i data-lucide="heart" class="size-5" aria-hidden="true"></i>
    <span data-vani-wishlist-count hidden class="absolute -right-1 -top-1 min-w-5 rounded-full bg-primary px-1.5 text-center text-xs leading-5 text-white"></span>
</a>
