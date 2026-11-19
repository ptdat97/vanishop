@extends('theme::layouts.app')

@section('title', __('theme::common.track_order'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('theme::common.track_order') }}</h1>
    <form action="{{ vani_route('storefront.track.search') }}" method="post" class="mb-8 grid max-w-xl gap-3 sm:grid-cols-[1fr_1fr_auto]">
        @csrf
        <div>
            <label for="number" class="block text-sm">{{ __('theme::order.order_number') }}</label>
            <input id="number" name="number" value="{{ old('number', request('number')) }}" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label for="track-phone" class="block text-sm">{{ __('theme::order.track_phone_hint') }}</label>
            <input id="track-phone" name="phone" type="tel" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <button type="submit" class="self-end rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::order.look_up') }}</button>
    </form>

    @if (! empty($notFound))
        <p role="alert" class="text-sm text-red-600">{{ __('theme::order.track_not_found') }}</p>
    @elseif ($order)
        <h2 class="mb-2 font-semibold">{{ __('theme::common.order_title', ['number' => $order['number']]) }} · {{ $order['status']['label'] ?? $order['order_status'] }}</h2>
        @include('theme::partials.order-summary')
        <x-vani::hook-slot name="vani.storefront.track.after_order" :args="[$order]" />
    @endif
@endsection
