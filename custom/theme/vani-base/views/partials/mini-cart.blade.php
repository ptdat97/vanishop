{{--
    Nội dung giỏ mini (offcanvas, 0.3.93): server render rồi trả trong JSON (`html`) — CartController::payload(). Theme con
    override file này để đổi giao diện; nút dùng thuộc tính data-* để app.js bắt sự kiện:
      [data-cart-quantity][data-line][data-quantity]  đặt số lượng dòng · [data-cart-remove][data-line]  xoá dòng
      form[data-cart-voucher-form]  áp/bỏ mã giảm giá
    $cart: null | giỏ như Storefront API (CartPresenter::present) · $voucher: null | CartVoucher::preview.
--}}
@php([$ratioW, $ratioH] = vani_product_ratio())
@if ($cart === null || $cart['lines'] === [])
    <div class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center text-slate-500">
        <p>{{ __('Giỏ hàng trống.') }}</p>
        <a href="{{ vani_route('storefront.home') }}" class="underline" data-cart-close>{{ __('Tiếp tục mua sắm') }}</a>
    </div>
@else
    <ul class="flex-1 divide-y divide-slate-100 overflow-y-auto px-4" aria-label="{{ __('Sản phẩm trong giỏ') }}">
        @foreach ($cart['lines'] as $line)
            <li class="flex gap-3 py-4" data-line="{{ $line['id'] }}">
                <div class="w-16 shrink-0 overflow-hidden rounded bg-slate-100" style="aspect-ratio: {{ $ratioW }} / {{ $ratioH }}">
                    @if ($line['product']['image_url'] ?? null)
                        <img src="{{ $line['product']['image_url'] }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1 text-sm">
                    @if ($line['product'])
                        <a href="{{ vani_route('storefront.product', $line['product']['slug']) }}" class="line-clamp-2 font-medium">{{ $line['product']['name'] }}</a>
                        <p class="text-slate-500">{{ $line['product']['color_name'] }} / {{ $line['product']['size_code'] }}</p>
                        @include('theme::partials.line-options', ['options' => $line['options']])
                    @else
                        <p class="font-medium text-slate-500">{{ __('Sản phẩm không còn bán') }}</p>
                    @endif
                    @foreach ($line['issues'] as $issue)
                        <p class="text-red-600">{{ __('storefront::messages.issue_'.$issue) }}</p>
                    @endforeach
                    <div class="mt-2 flex items-center gap-3">
                        <div class="flex items-center rounded border border-slate-300" role="group" aria-label="{{ __('Số lượng') }}">
                            <button type="button" class="px-2 py-0.5 disabled:opacity-40" data-cart-quantity data-line="{{ $line['id'] }}" data-quantity="{{ $line['quantity'] - 1 }}"
                                    @disabled($line['quantity'] <= 1) aria-label="{{ __('Giảm số lượng') }}">−</button>
                            <span class="min-w-6 text-center tabular-nums" aria-live="polite">{{ $line['quantity'] }}</span>
                            <button type="button" class="px-2 py-0.5" data-cart-quantity data-line="{{ $line['id'] }}" data-quantity="{{ $line['quantity'] + 1 }}"
                                    aria-label="{{ __('Tăng số lượng') }}">+</button>
                        </div>
                        <button type="button" class="text-slate-500 underline" data-cart-remove data-line="{{ $line['id'] }}">{{ __('Xoá') }}</button>
                    </div>
                </div>
                <p class="shrink-0 text-sm font-medium">{{ $line['line_total']['formatted'] ?? '—' }}</p>
            </li>
        @endforeach
    </ul>

    <div class="space-y-3 border-t border-slate-200 p-4 text-sm">
        @include('theme::partials.cart-voucher', ['voucher' => $voucher])
        <dl class="space-y-1">
            <div class="flex justify-between"><dt>{{ __('Tạm tính (:count sản phẩm đã chọn)', ['count' => $cart['selected_count']]) }}</dt><dd>{{ $cart['selected_subtotal']['formatted'] }}</dd></div>
            @if ($voucher && $voucher['rejection'] === null && $voucher['discount']['amount'] > 0)
                <div class="flex justify-between text-emerald-700"><dt>{{ __('Giảm giá') }}</dt><dd>−{{ $voucher['discount']['formatted'] }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>{{ __('Tổng tạm tính') }}</dt><dd>{{ $voucher['total']['formatted'] }}</dd></div>
            @endif
        </dl>
        <p class="text-xs text-slate-500">{{ __('Phí giao hàng tính ở bước thanh toán.') }}</p>
        @if ($cart['checkout_ready'] && $cart['selected_count'] < $cart['item_count'])
            <p class="text-xs text-slate-500">{{ __('Chỉ sản phẩm đã chọn được thanh toán — đổi lựa chọn ở trang giỏ hàng.') }}</p>
        @endif
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ vani_route('storefront.cart') }}" class="rounded-[var(--radius-theme)] border border-primary px-4 py-2.5 text-center font-medium text-primary">{{ __('Xem giỏ hàng') }}</a>
            @include('theme::partials.checkout-button', ['cart' => $cart, 'class' => 'rounded-[var(--radius-theme)] bg-primary px-4 py-2.5 text-center font-medium text-white'])
        </div>
    </div>
@endif
