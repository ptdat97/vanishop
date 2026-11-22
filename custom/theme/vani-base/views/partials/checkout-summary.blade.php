<aside class="h-fit min-w-0 space-y-4 border border-border bg-subtle p-5 text-sm lg:p-7" data-checkout-summary>
    <h2 class="text-xl font-medium tracking-tight">{{ __('theme::checkout.order_summary') }}</h2>
    <ul class="divide-y divide-border">
        @foreach ($quote['lines'] as $line)
            <li class="flex items-start justify-between gap-3 py-2">
                <span class="min-w-0 break-words">{{ $line['name'] }} <span class="whitespace-nowrap text-accent">× {{ $line['quantity'] }}</span></span>
                <span class="shrink-0">{{ $line['total']['formatted'] }}</span>
            </li>
        @endforeach
    </ul>
    <x-vani::hook-slot name="vani.storefront.checkout.order_summary" :args="[$quote]" />

    <div class="flex gap-2">
        <label for="voucher_code" class="sr-only">{{ __('theme::common.discount_code') }}</label>
        <input id="voucher_code" name="voucher_code" value="{{ $voucherCode ?? old('voucher_code') }}" placeholder="{{ __('theme::common.discount_code') }}" class="min-w-0 flex-1 rounded border border-slate-300 px-2 py-1.5">
        <button type="submit" name="action" value="quote" class="min-h-11 rounded-[var(--radius-theme)] border border-slate-300 px-3">{{ __('theme::common.apply') }}</button>
    </div>
    {{-- 0.3.104: mã không dùng được không bị giữ lại (đặt hàng không bị chặn) — chỉ nhắc nhẹ khi khách vừa tự nhập. --}}
    @if ($voucherNotice ?? null)
        <p class="text-slate-500" data-voucher-notice>{{ $voucherNotice }} {{ __('theme::checkout.voucher_optional_hint') }}</p>
    @endif

    <dl class="space-y-1 border-t border-slate-200 pt-3">
        <div class="flex justify-between"><dt>{{ __('theme::checkout.subtotal') }}</dt><dd data-vani-value="quote.subtotal" data-vani-amount="{{ $quote['subtotal']['amount'] }}">{{ $quote['subtotal']['formatted'] }}</dd></div>
        @if ($quote['discount']['amount'] > 0)
            <div class="flex justify-between text-accent"><dt>{{ __('theme::common.discount') }}</dt><dd data-vani-value="quote.discount" data-vani-amount="{{ $quote['discount']['amount'] }}">-{{ $quote['discount']['formatted'] }}</dd></div>
        @endif
        <div class="flex justify-between"><dt>{{ __('theme::checkout.shipping') }}</dt><dd data-vani-value="quote.shipping_fee" data-vani-amount="{{ $quote['shipping_fee']['amount'] }}">{{ $quote['shipping_fee']['formatted'] }}</dd></div>
        <div class="mt-3 flex justify-between gap-3 border-t border-border pt-3 text-lg font-medium"><dt>{{ __('theme::checkout.total') }}</dt><dd data-total data-vani-value="quote.total" data-vani-amount="{{ $quote['total']['amount'] }}">{{ $quote['total']['formatted'] }}</dd></div>
        @if ($quote['tax_included']['amount'] > 0)
            <p class="text-xs text-slate-500">{{ __('theme::checkout.tax_included_amount', ['amount' => $quote['tax_included']['formatted']]) }}</p>
        @endif
    </dl>

    <x-vani::hook-slot name="vani.storefront.checkout.after_summary" :args="[$quote]" />
    <x-vani::hook-slot name="vani.storefront.checkout.before_submit" :args="[$quote]" />
    <button type="submit" class="vani-button-primary hidden w-full lg:inline-flex" data-checkout-submit>{{ __('theme::checkout.place_order') }}</button>
</aside>
