{{--
    Nút tim (đặc tả §2): form POST — chạy khi tắt JS ở trang không cache CDN. HTML giống nhau với mọi khách (trang cache
    CDN); JS (wishlist.js) tô trạng thái theo danh sách của khách và gửi bằng fetch. $styleId, $compact (thẻ sản phẩm).
--}}
<form action="{{ vani_route('storefront.p.vani-wishlist.toggle') }}" method="post" data-vani-wishlist="{{ $styleId }}" @class(['inline-block', 'mt-1' => $compact, 'mt-3' => ! $compact])>
    @unless (request()->attributes->has('vani.page_cache'))
        @csrf
    @endunless
    <input type="hidden" name="style_id" value="{{ $styleId }}">
    <button type="submit" aria-pressed="false" class="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-primary aria-pressed:text-primary" data-vani-wishlist-button>
        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20s-7-4.4-9.2-8.6C1.3 8.4 3 5 6.4 5c2 0 3.6 1.1 4.6 2.7C12 6.1 13.6 5 15.6 5 19 5 20.7 8.4 21.2 11.4 19 15.6 12 20 12 20z"/></svg>
        <span @class(['sr-only' => $compact]) data-vani-wishlist-label>{{ __('Yêu thích') }}</span>
    </button>
</form>
