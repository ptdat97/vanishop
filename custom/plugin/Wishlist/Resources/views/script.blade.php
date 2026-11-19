{{-- Script của plugin (tài nguyên tĩnh có hash, Core 0.3.69): cấu hình qua data-* — URL theo ngôn ngữ trang, provider dựng sẵn. --}}
@if ($scriptSrc !== '')
    <script src="{{ $scriptSrc }}" defer data-vani-wishlist-script
            data-ids-url="{{ $urls['ids'] }}"
            data-items-url="{{ $urls['items'] }}"
            data-merge-url="{{ $urls['merge'] }}"
            data-label-add="{{ __('vani-wishlist::storefront.wishlist') }}" data-label-remove="{{ __('vani-wishlist::storefront.remove_from_wishlist') }}"></script>
@endif
