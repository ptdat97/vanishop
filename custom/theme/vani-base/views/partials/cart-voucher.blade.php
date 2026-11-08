{{--
    Mã giảm giá của giỏ (0.3.93) — trang giỏ và giỏ mini dùng chung. Form POST thường (không JS: về trang giỏ); app.js gửi JSON.
    $voucher: null | {code, discount, total, rejection} (CartVoucher::preview). Checkout điền sẵn mã này.
--}}
<div class="space-y-2" data-cart-voucher>
    @if ($voucher)
        <div class="flex items-center justify-between gap-2 rounded border border-dashed border-slate-300 px-3 py-2 text-sm">
            <span>{{ __('Mã :code', ['code' => $voucher['code']]) }}
                @if ($voucher['rejection'] === null && $voucher['discount']['amount'] > 0)<span class="text-emerald-700">(−{{ $voucher['discount']['formatted'] }})</span>@endif
            </span>
            <form action="{{ vani_route('storefront.cart.voucher') }}" method="post" data-cart-voucher-form>
                @csrf
                <input type="hidden" name="code" value="">
                <button type="submit" class="text-slate-500 underline">{{ __('Bỏ mã') }}</button>
            </form>
        </div>
    @else
        <form action="{{ vani_route('storefront.cart.voucher') }}" method="post" class="flex gap-2" data-cart-voucher-form>
            @csrf
            <label for="cart-voucher-code" class="sr-only">{{ __('Mã giảm giá') }}</label>
            <input id="cart-voucher-code" name="code" autocomplete="off" placeholder="{{ __('Mã giảm giá') }}" required maxlength="64"
                   class="min-w-0 flex-1 rounded border border-slate-300 px-2 py-1.5 text-sm uppercase">
            <button type="submit" class="rounded border border-slate-300 px-3 text-sm">{{ __('Áp dụng') }}</button>
        </form>
    @endif
    <p role="alert" class="text-sm text-red-600" data-cart-voucher-error @unless ($errors->has('code')) hidden @endunless>{{ $errors->first('code') }}</p>
</div>
