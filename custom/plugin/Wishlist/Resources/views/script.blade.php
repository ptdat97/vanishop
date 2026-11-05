{{-- Script của plugin (tài nguyên tĩnh có hash, Core 0.3.69): cấu hình qua data-* — URL theo ngôn ngữ trang. --}}
@php($src = vani_asset('vani.wishlist', 'wishlist.js'))
@if ($src !== '')
    <script src="{{ $src }}" defer data-vani-wishlist-script
            data-ids-url="{{ vani_route('storefront.p.vani-wishlist.ids') }}"
            data-toggle-url="{{ vani_route('storefront.p.vani-wishlist.toggle') }}"
            data-merge-url="{{ vani_route('storefront.p.vani-wishlist.merge') }}"
            data-label-add="{{ __('Yêu thích') }}" data-label-remove="{{ __('Bỏ yêu thích') }}"></script>
@endif
