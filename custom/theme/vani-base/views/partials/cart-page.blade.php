{{--
    Thân trang giỏ (0.3.94) — trang `pages.cart` và JSON của CartController (`page_html`, khi JS trên trang giỏ gửi
    `X-Vani-Cart-View: page`) dùng chung: app.js thay vùng [data-vani-cart-page] sau mỗi thao tác, không tải lại trang.
    $cart: null | giỏ (CartPresenter::present) · $voucher: null | CartVoucher::preview.
--}}
<p role="alert" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700" data-vani-cart-error hidden></p>
@if ($cart === null || $cart['lines'] === [])
    @include('theme::partials.empty-state', [
        'emptyMessage' => __('theme::cart.cart_empty'),
        'emptyClass' => 'bg-subtle px-4 py-12 text-center text-slate-600',
        'emptyAction' => ['url' => vani_route('storefront.home'), 'label' => __('theme::cart.continue_shopping')],
    ])
    <x-vani::hook-slot name="vani.storefront.cart.empty" />
@else
    <x-vani::hook-slot name="vani.storefront.cart.before_lines" :args="[$cart]" />
    {{-- Chọn dòng để thanh toán (§3.8): ô chọn thuộc form này qua thuộc tính form=, JS tự gửi khi đổi. --}}
    <form id="cart-selection" action="{{ vani_route('storefront.cart.select') }}" method="post" class="mb-3 flex items-center gap-4 text-sm">
        @csrf
        <label class="flex min-h-11 items-center gap-2">
            <input id="cart-select-all" type="checkbox" class="size-5 accent-primary" data-select-all @checked(collect($cart['lines'])->every(fn ($line) => $line['selected'] || in_array('unavailable', $line['issues'], true)))>
            {{ __('theme::cart.select_all') }}
        </label>
    </form>
    <ul class="divide-y divide-slate-200 border-y border-slate-200">
        @foreach ($cart['lines'] as $line)
            <li class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2 py-4 sm:grid-cols-[auto_minmax(0,1fr)_auto_auto_auto] sm:gap-4">
                @php($unavailable = in_array('unavailable', $line['issues'], true))
                <label class="col-start-1 row-start-1 flex min-h-11 min-w-11 items-center justify-center self-start">
                    <span class="sr-only">{{ __('theme::cart.select_checkout') }} · {{ $line['product']['name'] ?? $line['sku'] }}</span>
                    <input id="cart-select-{{ $line['id'] }}" type="checkbox" name="lines[]" value="{{ $line['id'] }}" form="cart-selection" data-select-line class="size-5 accent-primary" @checked($line['selected']) @disabled($unavailable)>
                </label>
                <div class="col-span-2 min-w-0 wrap-anywhere sm:col-span-1">
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
                <form action="{{ vani_route('storefront.cart.update', $line['id']) }}" method="post" class="col-span-2 col-start-1 row-start-2 flex items-center gap-1 sm:col-span-1 sm:col-start-3 sm:row-start-1" data-cart-update-form data-line="{{ $line['id'] }}">
                    @csrf
                    <button id="qty-less-{{ $line['id'] }}" type="button" data-js-only data-cart-step="-1" aria-controls="qty-{{ $line['id'] }}" @if ($line['quantity'] <= 1) aria-disabled="true" @endif
                        aria-label="{{ __('theme::cart.decrease_quantity') }}" class="size-11 shrink-0 rounded border border-border aria-disabled:opacity-50">−</button>
                    <label for="qty-{{ $line['id'] }}" class="sr-only">{{ __('theme::common.quantity') }} · {{ $line['product']['name'] ?? $line['sku'] }}</label>
                    <input id="qty-{{ $line['id'] }}" name="quantity" type="number" min="0" max="20" value="{{ $line['quantity'] }}" inputmode="numeric" data-autosubmit data-line="{{ $line['id'] }}" class="min-h-11 w-11 rounded border border-border text-center text-base [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                    <button id="qty-more-{{ $line['id'] }}" type="button" data-js-only data-cart-step="1" aria-controls="qty-{{ $line['id'] }}" @if ($line['quantity'] >= 20) aria-disabled="true" @endif
                        aria-label="{{ __('theme::cart.increase_quantity') }}" class="size-11 shrink-0 rounded border border-border aria-disabled:opacity-50">+</button>
                    <noscript><button type="submit" class="vani-text-link">{{ __('theme::common.apply') }}</button></noscript>
                </form>
                <p class="col-start-3 row-start-2 text-right font-medium sm:col-start-4 sm:row-start-1" data-vani-value="line.total" data-vani-line="{{ $line['id'] }}" data-vani-amount="{{ $line['line_total']['amount'] ?? '' }}">{{ $line['line_total']['formatted'] ?? '—' }}</p>
                <form action="{{ vani_route('storefront.cart.remove', $line['id']) }}" method="post" class="col-span-2 col-start-2 row-start-3 justify-self-end sm:col-span-1 sm:col-start-5 sm:row-start-1" data-cart-remove-form data-line="{{ $line['id'] }}">
                    @csrf
                    <button type="submit" class="vani-text-link text-accent">{{ __('theme::common.remove') }}</button>
                </form>
            </li>
        @endforeach
    </ul>
    <x-vani::hook-slot name="vani.storefront.cart.after_lines" :args="[$cart]" />

    <div class="mt-6 ml-auto flex max-w-md flex-col gap-4 bg-subtle p-4">
        <div class="w-full">@include('theme::partials.cart-voucher', ['voucher' => $voucher ?? null])</div>
        <p>{{ __('theme::cart.subtotal_selected_colon', ['count' => $cart['selected_count']]) }} <strong data-vani-value="cart.selected_subtotal" data-vani-amount="{{ $cart['selected_subtotal']['amount'] }}">{{ $cart['selected_subtotal']['formatted'] }}</strong></p>
        @if (($discountSummary['discount']['amount'] ?? 0) > 0)
            <p class="text-emerald-700">{{ __('theme::common.discount') }}: −{{ $discountSummary['discount']['formatted'] }} · {{ __('theme::cart.estimated_total') }}: <strong>{{ $discountSummary['total']['formatted'] }}</strong></p>
        @endif
        {{-- Chưa sẵn sàng: nút trên chọn lại mọi sản phẩm còn mua được; câu dưới giải thích cho khách. --}}
        @if (! $cart['checkout_ready'] && ($cart['payable_line_ids'] ?? []) !== [])
            <p class="text-sm text-slate-600">{{ __('theme::cart.checkout_all_hint') }}</p>
        @elseif (! $cart['checkout_ready'])
            <p class="text-sm text-red-600">{{ __('theme::cart.fix_lines_before_checkout') }}</p>
        @endif
    </div>
    <div class="sticky bottom-0 z-20 mt-4 -mx-4 grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-t border-border bg-surface px-4 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] md:static md:mx-0 md:ml-auto md:max-w-md md:px-0" data-cart-bottom>
        <div class="min-w-0 text-sm">
            @if (($discountSummary['discount']['amount'] ?? 0) > 0)
                <span class="block text-accent">{{ __('theme::cart.estimated_total') }}</span>
                <strong>{{ $discountSummary['total']['formatted'] }}</strong>
            @else
                <span class="block text-accent">{{ __('theme::checkout.subtotal') }}</span>
                <strong>{{ $cart['selected_subtotal']['formatted'] }}</strong>
            @endif
        </div>
        @include('theme::partials.checkout-button', ['cart' => $cart, 'class' => 'vani-button-primary px-3 sm:px-6'])
    </div>
    <x-vani::hook-slot name="vani.storefront.cart.after_summary" :args="[$cart]" />
@endif
