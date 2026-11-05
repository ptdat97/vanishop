{{-- Link tới danh sách yêu thích + số món (JS điền). Chưa đăng nhập → trang đăng nhập, rồi quay lại danh sách. --}}
<a href="{{ vani_route('storefront.p.vani-wishlist.account.index') }}" class="inline-flex items-center gap-1" data-vani-wishlist-link>
    {{ __('Yêu thích') }} <span data-vani-wishlist-count hidden class="rounded-full bg-primary px-1.5 text-xs text-white"></span>
</a>
