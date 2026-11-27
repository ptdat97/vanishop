{{-- Ví voucher ở giỏ (slot cart.after_summary) và checkout (slot checkout.order_summary). $context: cart | checkout. --}}
<section class="space-y-2 rounded border border-border p-3 text-sm" data-vani-coupon-wallet="{{ $context }}">
    <div class="flex items-center justify-between gap-2">
        <h2 class="font-medium">{{ __('vani-coupon-code::texts.wallet.title') }}</h2>
        @if ($best !== null && ! collect($items)->firstWhere('code', $best)['applied'])
            <button type="button" class="vani-text-link text-sm" data-vani-coupon-use="{{ $best }}">{{ __('vani-coupon-code::texts.wallet.best') }}</button>
        @endif
    </div>
    <ul class="divide-y divide-border">
        @foreach ($items as $offer)
            <li class="flex items-start gap-3 py-2" data-vani-coupon="{{ $offer['code'] }}">
                @include('vani-coupon-code::offer', ['offer' => $offer])
                <div class="shrink-0 text-end">
                    @if ($offer['applicable'])<p class="text-accent">−{{ $offer['discount']['formatted'] }}</p>@endif
                    @if ($offer['applied'])
                        <span class="text-xs text-emerald-700">{{ __('vani-coupon-code::texts.wallet.applied') }}</span>
                    @elseif ($offer['applicable'])
                        <button type="button" class="rounded border border-slate-300 px-2 py-1 text-xs" data-vani-coupon-use="{{ $offer['code'] }}">{{ __('vani-coupon-code::texts.wallet.use') }}</button>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
    <script src="{{ $scriptSrc }}" defer data-vani-coupon-script></script>
</section>
