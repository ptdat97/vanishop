{{-- Script của plugin (tài nguyên tĩnh có hash, Core 0.3.69): cấu hình qua data-* — URL theo ngôn ngữ trang, provider dựng sẵn. --}}
@if ($scriptSrc !== '')
    <script src="{{ $scriptSrc }}" defer data-vani-wishlist-script
            data-ids-url="{{ $urls['ids'] }}"
            data-items-url="{{ $urls['items'] }}"
            data-merge-url="{{ $urls['merge'] }}"
            data-label-add="{{ __('Yêu thích') }}" data-label-remove="{{ __('Bỏ yêu thích') }}"></script>
@endif
