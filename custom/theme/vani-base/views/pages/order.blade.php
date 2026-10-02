@extends('theme::layouts.app')

@section('title', 'Đơn '.$order['number'])

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Cảm ơn bạn đã đặt hàng!</h1>
    <p class="mb-6">Mã đơn: <strong data-order-number>{{ $order['number'] }}</strong> · {{ $order['status']['label'] ?? $order['order_status'] }}</p>

    @if (($payment['action']['instructions'] ?? null) !== null)
        <section class="mb-6 rounded-[var(--radius-theme)] border border-amber-300 bg-amber-50 p-4 text-sm">
            <h2 class="mb-2 font-semibold">Hướng dẫn thanh toán</h2>
            <dl class="grid grid-cols-[10rem_1fr] gap-y-1">
                @foreach ($payment['action']['instructions'] as $field => $value)
                    <dt class="text-slate-600">{{ __('storefront::payment.'.$field) }}</dt><dd class="font-medium">{{ $value }}</dd>
                @endforeach
            </dl>
        </section>
    @endif

    @include('theme::partials.order-summary')

    <x-vani::hook-slot name="vani.storefront.order.after_summary" :args="[$order]" />
@endsection
