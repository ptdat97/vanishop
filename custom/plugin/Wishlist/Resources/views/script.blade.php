{{-- Script của plugin (tài nguyên tĩnh có hash, Core 0.3.69): cấu hình qua data-* — URL theo ngôn ngữ trang. --}}
@php($src = vani_asset('vani.wishlist', 'wishlist.js'))
@if ($src !== '')
    <script src="{{ $src }}" defer data-vani-wishlist-script
            data-ids-url="{{ route('api.storefront.x.vani-wishlist.ids') }}"
            data-items-url="{{ route('api.storefront.x.vani-wishlist.items.store') }}"
            data-merge-url="{{ route('api.storefront.x.vani-wishlist.items.merge') }}"
            data-label-add="{{ __('Yêu thích') }}" data-label-remove="{{ __('Bỏ yêu thích') }}"></script>
@endif
