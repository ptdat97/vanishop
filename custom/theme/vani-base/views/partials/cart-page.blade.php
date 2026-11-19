{{--
    Thân trang giỏ (0.3.94) — trang `pages.cart` và JSON của CartController (`page_html`, khi JS trên trang giỏ gửi
    `X-Vani-Cart-View: page`) dùng chung: app.js thay vùng [data-vani-cart-page] sau mỗi thao tác, không tải lại trang.
    Không JS: các form POST như cũ. $cart: null | giỏ (CartPresenter::present) · $voucher: null | CartVoucher::preview.
--}}
<p role="alert" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700" data-vani-cart-error hidden></p>
@if ($cart === null || $cart['lines'] === [])
    <p class="text-slate-500">{{ __('theme::cart.cart_empty') }} <a href="{{ vani_route('storefront.home') }}" class="underline">{{ __('theme::cart.continue_shopping') }}</a></p>
    <x-vani::hook-slot name="vani.storefront.cart.empty" />
@else
    <x-vani::hook-slot name="vani.storefront.cart.before_lines" :args="[$cart]" />
    {{-- Chọn dòng để thanh toán (§3.8): ô chọn thuộc form này qua thuộc tính form=, JS tự gửi khi đổi; không JS: nút "Áp dụng lựa chọn". --}}
    <form id="cart-selection" action="{{ vani_route('storefront.cart.select') }}" method="post" class="mb-3 flex items-center gap-4 text-sm">
        @csrf
        <label class="flex items-center gap-2">
            <input type="checkbox" data-select-all @checked(collect($cart['lines'])->every(fn ($line) => $line['selected'] || in_array('unavailable', $line['issues'], true)))>
            {{ __('theme::cart.select_all') }}
        </label>
        <button type="submit" class="underline" data-selection-submit data-no-js>{{ __('theme::cart.apply_selection') }}</button>
    </form>
    <ul class="divide-y divide-slate-200 border-y border-slate-200">
        @foreach ($cart['lines'] as $line)
            <li class="flex flex-wrap items-center gap-4 py-4">
                @php($unavailable = in_array('unavailable', $line['issues'], true))
                <label class="flex items-center">
                    <span class="sr-only">{{ __('theme::cart.select_checkout') }}</span>
                    <input type="checkbox" name="lines[]" value="{{ $line['id'] }}" form="cart-selection" data-select-line data-line="{{ $line['id'] }}" @checked($line['selected']) @disabled($unavailable)>
                </label>
                <div class="min-w-48 flex-1">
                    @if ($line['product'])
                        <a href="{{ vani_route('storefront.product', $line['product']['slug']) }}" class="font-medium">{{ $line['product']['name'] }}</a>
                        <p class="text-sm text-slate-500">{{ $line['product']['variant_label'] !== '' ? $line['product']['variant_label'].' · ' : '' }}{{ $line['sku'] }}</p>
                        @include('theme::partials.line-options', ['options' => $line['options']])
                    @else
                        <p class="font-medium text-slate-500">{{ __('theme::cart.product_no_longer_available') }}</p>
                    @endif
                    @foreach ($line['issues'] as $issue)
                        <p class="text-sm text-red-600">{{ __('storefront::messages.issue_'.$issue) }}</p>
                    @endforeach
                </div>
                <form action="{{ vani_route('storefront.cart.update', $line['id']) }}" method="post" class="flex items-center gap-2" data-cart-update-form data-line="{{ $line['id'] }}">
                    @csrf
                    <label for="qty-{{ $line['id'] }}" class="sr-only">{{ __('theme::common.quantity') }}</label>
                    <input id="qty-{{ $line['id'] }}" name="quantity" type="number" min="0" max="20" value="{{ $line['quantity'] }}" data-autosubmit data-line="{{ $line['id'] }}" class="w-16 rounded border border-slate-300 px-2 py-1">
                    <button type="submit" class="text-sm underline" data-no-js>{{ __('theme::cart.update') }}</button>
                </form>
                <p class="w-28 text-right font-medium" data-vani-value="line.total" data-vani-line="{{ $line['id'] }}" data-vani-amount="{{ $line['line_total']['amount'] ?? '' }}">{{ $line['line_total']['formatted'] ?? '—' }}</p>
                <form action="{{ vani_route('storefront.cart.remove', $line['id']) }}" method="post" data-cart-remove-form data-line="{{ $line['id'] }}">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 underline">{{ __('theme::common.remove') }}</button>
                </form>
            </li>
        @endforeach
    </ul>
    <x-vani::hook-slot name="vani.storefront.cart.after_lines" :args="[$cart]" />

    <div class="mt-6 flex flex-col items-end gap-3">
        <div class="w-full max-w-sm">@include('theme::partials.cart-voucher', ['voucher' => $voucher ?? null])</div>
        <p>{{ __('theme::cart.subtotal_selected_colon', ['count' => $cart['selected_count']]) }} <strong data-vani-value="cart.selected_subtotal" data-vani-amount="{{ $cart['selected_subtotal']['amount'] }}">{{ $cart['selected_subtotal']['formatted'] }}</strong></p>
        @if (($voucher ?? null) && $voucher['rejection'] === null && $voucher['discount']['amount'] > 0)
            <p class="text-emerald-700">{{ __('theme::common.discount') }}: −{{ $voucher['discount']['formatted'] }} · {{ __('theme::cart.estimated_total') }}: <strong>{{ $voucher['total']['formatted'] }}</strong></p>
        @endif
        @include('theme::partials.checkout-button', ['cart' => $cart, 'class' => 'rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white'])
        {{-- Chưa sẵn sàng: nút trên chọn lại mọi sản phẩm còn mua được; câu dưới giải thích cho khách. --}}
        @if (! $cart['checkout_ready'] && ($cart['payable_line_ids'] ?? []) !== [])
            <p class="text-sm text-slate-600">{{ __('theme::cart.checkout_all_hint') }}</p>
        @elseif (! $cart['checkout_ready'])
            <p class="text-sm text-red-600">{{ __('theme::cart.fix_lines_before_checkout') }}</p>
        @endif
    </div>
    <x-vani::hook-slot name="vani.storefront.cart.after_summary" :args="[$cart]" />
@endif
