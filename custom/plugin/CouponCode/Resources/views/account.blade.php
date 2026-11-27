@extends('theme::layouts.app')

@section('title', __('vani-coupon-code::texts.account.title'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[14rem_1fr]">
        @include('theme::partials.account-menu')
        <section class="space-y-6 text-sm">
            <h1 class="text-2xl font-semibold">{{ __('vani-coupon-code::texts.account.title') }}</h1>
            @foreach (['usable', 'used', 'expired'] as $group)
                <div class="space-y-2" data-vani-coupon-group="{{ $group }}">
                    <h2 class="font-medium">{{ __('vani-coupon-code::texts.account.'.$group) }}</h2>
                    @forelse ($vouchers[$group] as $offer)
                        <div class="flex items-start gap-3 rounded border border-border p-3 {{ $group === 'usable' ? '' : 'opacity-60' }}">
                            @include('vani-coupon-code::offer', ['offer' => $offer])
                            @if ($group === 'usable')
                                <button type="button" class="shrink-0 rounded border border-slate-300 px-2 py-1 text-xs" data-vani-coupon-copy="{{ $offer['code'] }}">{{ __('vani-coupon-code::texts.pdp.copy') }}</button>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-500">{{ __('vani-coupon-code::texts.account.empty') }}</p>
                    @endforelse
                </div>
            @endforeach
            <script src="{{ $scriptSrc }}" defer data-vani-coupon-script data-copied="{{ __('vani-coupon-code::texts.pdp.copied') }}"></script>
        </section>
    </div>
@endsection
