<div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-slate-200 bg-white px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] lg:hidden" data-checkout-bar>
    <a href="#checkout-summary" class="flex min-h-11 min-w-0 flex-1 flex-col justify-center text-sm" aria-label="{{ __('theme::checkout.order_summary') }}">
        <span class="block text-slate-500">{{ __('theme::checkout.total') }}</span>
        <span class="text-base font-semibold underline underline-offset-4" data-total>{{ $quote['total']['formatted'] }}</span>
    </a>
    <button type="submit" class="vani-button-primary shrink-0 px-4" data-checkout-submit>{{ __('theme::checkout.place_order') }}</button>
</div>
