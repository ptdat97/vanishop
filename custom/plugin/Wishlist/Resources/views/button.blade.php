{{--
    Nút tim (đặc tả §2): wishlist.js thêm/bỏ qua Storefront API và tô trạng thái theo danh sách của khách. HTML giống nhau
    với mọi khách (trang cache CDN). $styleId, $compact (thẻ sản phẩm: nút nổi góc trên phải ảnh — thẻ <a> cha là relative).
--}}
<div data-vani-wishlist="{{ $styleId }}" @class(['absolute right-2 top-2 z-10' => $compact, 'mt-3 inline-block' => ! $compact])>
    <button type="button" aria-pressed="false" @class(['inline-flex items-center gap-1 text-sm text-slate-600 hover:text-primary aria-pressed:text-primary', 'rounded-full bg-white/90 p-1.5 shadow-sm' => $compact]) data-vani-wishlist-button>
        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20s-7-4.4-9.2-8.6C1.3 8.4 3 5 6.4 5c2 0 3.6 1.1 4.6 2.7C12 6.1 13.6 5 15.6 5 19 5 20.7 8.4 21.2 11.4 19 15.6 12 20 12 20z"/></svg>
        <span @class(['sr-only' => $compact]) data-vani-wishlist-label>{{ __('vani-wishlist::storefront.wishlist') }}</span>
    </button>
</div>
