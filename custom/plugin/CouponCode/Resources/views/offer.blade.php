{{-- Một mã: ưu đãi, điều kiện, HSD. $offer: CouponPresenter Offer (+ applicable/applied/message ở ví). --}}
<div class="min-w-0 flex-1">
    <p class="font-medium">{{ $offer['title'] }}
        <span class="ms-1 rounded border border-dashed border-slate-300 px-1.5 py-0.5 font-mono text-xs">{{ $offer['code'] }}</span>
        @if ($offer['personal'])<span class="ms-1 text-xs text-accent">{{ __('vani-coupon-code::texts.wallet.personal') }}</span>@endif
    </p>
    @if ($offer['description'] !== '')<p class="text-slate-600">{{ $offer['description'] }}</p>@endif
    @if ($offer['conditions'] !== [])<p class="text-xs text-slate-500">{{ implode(' · ', $offer['conditions']) }}</p>@endif
    <p class="text-xs text-slate-500">
        {{ $offer['expires_at'] === null ? __('vani-coupon-code::texts.offer.no_expiry') : __('vani-coupon-code::texts.offer.expires', ['date' => $offer['expires_at']]) }}
        @if ($offer['remaining'] !== null) · {{ __('vani-coupon-code::texts.offer.remaining', ['count' => $offer['remaining']]) }}@endif
    </p>
    @if (($offer['message'] ?? null) !== null)<p class="text-xs text-amber-700" data-vani-coupon-message>{{ $offer['message'] }}</p>@endif
</div>
