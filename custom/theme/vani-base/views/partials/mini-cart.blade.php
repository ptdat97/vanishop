{{--
    Nội dung giỏ mini (offcanvas, 0.3.93): server render rồi trả trong JSON (`html`) — CartController::payload(). Theme con
    override file này để đổi giao diện; nút dùng thuộc tính data-* để app.js bắt sự kiện:
      [data-cart-quantity][data-line][data-quantity]  đặt số lượng dòng · [data-cart-remove][data-line]  xoá dòng
      form[data-cart-voucher-form]  áp/bỏ mã giảm giá
    $cart: null | giỏ như Storefront API (CartPresenter::present) · $voucher: null | CartVoucher::preview.
--}}
@if ($cart === null || $cart['lines'] === [])
    @include('theme::partials.empty-state', [
        'emptyMessage' => __('theme::cart.cart_empty'),
        'emptyClass' => 'flex flex-1 flex-col items-center justify-center py-16 text-center text-slate-500',
        'emptyAction' => ['url' => vani_route('storefront.home'), 'label' => __('theme::cart.continue_shopping'), 'close_cart' => true],
    ])
@else
    <ul class="flex-1 divide-y divide-border overflow-y-auto px-4" aria-label="{{ __('theme::cart.items_cart') }}">
        @foreach ($cart['lines'] as $line)
            <li class="flex gap-3 py-4" data-line="{{ $line['id'] }}">
                <div class="w-16 shrink-0 self-start overflow-hidden rounded-[var(--radius-theme)] bg-subtle min-[390px]:w-20" style="aspect-ratio: {{ $productFrame['aspect'] }}">
                    @if ($line['product']['image_url'] ?? null)
                        <img src="{{ $line['product']['image_url'] }}" alt="" width="{{ $productFrame['card']['width'] }}" height="{{ $productFrame['card']['height'] }}" loading="lazy" decoding="async" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1 text-sm">
                    @if ($line['product'])
                        <a href="{{ vani_route('storefront.product', $line['product']['slug']) }}" class="line-clamp-2 font-medium">{{ $line['product']['name'] }}</a>
                        @if ($line['product']['variant_label'] !== '')<p class="text-slate-500">{{ $line['product']['variant_label'] }}</p>@endif
                        @include('theme::partials.line-options', ['options' => $line['options']])
                    @else
                        <p class="font-medium text-slate-500">{{ __('theme::cart.product_no_longer_available') }}</p>
                    @endif
                    @foreach ($line['issues'] as $issue)
                        <p class="text-red-600">{{ __('storefront::messages.issue_'.$issue) }}</p>
                    @endforeach
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <div class="flex items-center rounded-[var(--radius-theme)] border border-border" role="group" aria-label="{{ __('theme::common.quantity') }}">
                            <button type="button" class="size-11 disabled:opacity-40" data-cart-quantity data-line="{{ $line['id'] }}" data-quantity="{{ $line['quantity'] - 1 }}"
                                    @disabled($line['quantity'] <= 1) aria-label="{{ __('theme::cart.decrease_quantity') }}">−</button>
                            <span class="min-w-6 text-center tabular-nums" aria-live="polite">{{ $line['quantity'] }}</span>
                            <button type="button" class="size-11" data-cart-quantity data-line="{{ $line['id'] }}" data-quantity="{{ $line['quantity'] + 1 }}"
                                    aria-label="{{ __('theme::cart.increase_quantity') }}">+</button>
                        </div>
                        <button type="button" class="vani-text-link text-accent" data-cart-remove data-line="{{ $line['id'] }}">{{ __('theme::common.remove') }}</button>
                    </div>
                </div>
                <p class="shrink-0 text-sm font-medium">{{ $line['line_total']['formatted'] ?? '—' }}</p>
            </li>
        @endforeach
    </ul>

    <div class="space-y-3 border-t border-border bg-subtle p-4 pb-[max(1rem,env(safe-area-inset-bottom))] text-sm">
        @include('theme::partials.cart-voucher', ['voucher' => $voucher])
        <dl class="space-y-1">
            <div class="flex justify-between"><dt>{{ __('theme::cart.subtotal_selected', ['count' => $cart['selected_count']]) }}</dt><dd>{{ $cart['selected_subtotal']['formatted'] }}</dd></div>
            @if ($voucher && $voucher['rejection'] === null && $voucher['discount']['amount'] > 0)
                <div class="flex justify-between text-emerald-700"><dt>{{ __('theme::common.discount') }}</dt><dd>−{{ $voucher['discount']['formatted'] }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>{{ __('theme::cart.estimated_total') }}</dt><dd>{{ $voucher['total']['formatted'] }}</dd></div>
            @endif
        </dl>
        <p class="text-xs text-slate-500">{{ __('theme::cart.shipping_calculated_at_checkout') }}</p>
        @if ($cart['checkout_ready'] && $cart['selected_count'] < $cart['item_count'])
            <p class="text-xs text-slate-500">{{ __('theme::cart.selection_hint') }}</p>
        @endif
        <div class="flex flex-col items-center gap-1">
            @include('theme::partials.checkout-button', ['cart' => $cart, 'class' => 'vani-button-primary w-full'])
            <a href="{{ vani_route('storefront.cart') }}" class="vani-text-link">{{ __('theme::cart.view_cart') }}</a>
        </div>
    </div>
@endif
