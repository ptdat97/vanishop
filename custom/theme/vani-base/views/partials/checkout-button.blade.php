{{--
    Nút "Thanh toán" của giỏ (0.3.95) — giỏ mini và trang giỏ dùng chung. Giỏ sẵn sàng: link checkout (giữ lựa chọn của
    khách). Chưa sẵn sàng vì khách bỏ chọn hoặc dòng đã chọn hết hàng, nhưng giỏ còn dòng mua được: form chọn các dòng đó
    rồi sang checkout trong một request (CartController::select, `checkout=1`). Không còn dòng nào mua được: nút khoá.
    $cart: giỏ (CartPresenter::present) · $class: class của nút.
--}}
@if ($cart['checkout_ready'])
    <a href="{{ vani_route('storefront.checkout') }}" class="{{ $class }}">{{ __('Thanh toán') }}</a>
@elseif (($cart['payable_line_ids'] ?? []) !== [])
    <form action="{{ vani_route('storefront.cart.select') }}" method="post" class="contents" data-cart-checkout-form>
        @csrf
        <input type="hidden" name="checkout" value="1">
        @foreach ($cart['payable_line_ids'] as $lineId)
            <input type="hidden" name="lines[]" value="{{ $lineId }}">
        @endforeach
        <button type="submit" class="{{ $class }}">{{ __('Thanh toán') }}</button>
    </form>
@else
    <span class="{{ $class }} cursor-not-allowed opacity-50" aria-disabled="true">{{ __('Thanh toán') }}</span>
@endif
