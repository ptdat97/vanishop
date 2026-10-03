@extends('theme::layouts.app')

@section('title', 'Đơn '.$order['number'])

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Cảm ơn bạn đã đặt hàng!</h1>
    <p class="mb-6">Mã đơn: <strong data-order-number>{{ $order['number'] }}</strong> · {{ $order['status']['label'] ?? $order['order_status'] }}</p>

    @if (($payment['status'] ?? null) === 'paid')
        <p role="status" class="mb-6 rounded-[var(--radius-theme)] bg-emerald-50 px-4 py-2 text-sm text-emerald-800">Đã nhận thanh toán.</p>
    @elseif (in_array($payment['status'] ?? null, ['pending', 'failed'], true) && ($payment['action']['type'] ?? null) === 'redirect' && ! empty($payment['action']['url']))
        <section class="mb-6 rounded-[var(--radius-theme)] border border-amber-300 bg-amber-50 p-4 text-sm">
            <p class="mb-3">{{ ($payment['status'] ?? null) === 'failed' ? 'Thanh toán chưa thành công.' : 'Đơn đang chờ thanh toán.' }}</p>
            <a href="{{ $payment['action']['url'] }}" rel="noopener" class="inline-block rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">Thanh toán ngay</a>
        </section>
    @elseif (($payment['status'] ?? null) === 'pending' && ($payment['action']['type'] ?? null) === 'qr' && ! empty($payment['action']['qr']))
        <section class="mb-6 rounded-[var(--radius-theme)] border border-amber-300 bg-amber-50 p-4 text-sm">
            <h2 class="mb-2 font-semibold">Quét mã để thanh toán</h2>
            @if (str_starts_with($payment['action']['qr'], 'https://'))
                <img src="{{ $payment['action']['qr'] }}" alt="Mã QR thanh toán đơn {{ $order['number'] }}" class="h-56 w-56">
            @endif
        </section>
    @endif

    @if (($payment['action']['instructions'] ?? null) !== null && ($payment['status'] ?? null) !== 'paid')
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
