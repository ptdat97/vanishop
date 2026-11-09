@extends('theme::layouts.app')

@section('title', __('Đơn :number', ['number' => $order['number']]))

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">{{ __('Cảm ơn bạn đã đặt hàng!') }}</h1>
    <p class="mb-6">{{ __('Mã đơn:') }} <strong data-order-number>{{ $order['number'] }}</strong> · {{ $order['status']['label'] ?? $order['order_status'] }}</p>

    @if (($payment['status'] ?? null) === 'paid')
        <p role="status" class="mb-6 rounded-[var(--radius-theme)] bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ __('Đã nhận thanh toán.') }}</p>
    @elseif (($payment['retryable'] ?? false) && (($payment['action']['type'] ?? null) === 'redirect' || ($payment['status'] ?? null) === 'failed' || ($payment['action'] ?? null) === null))
        {{-- Thanh toán lại (§3.11-2): luôn qua POST để lấy link mới của cổng (link cũ có thể hết hạn); lỗi → lượt thanh toán mới. --}}
        <section class="mb-6 rounded-[var(--radius-theme)] border border-amber-300 bg-amber-50 p-4 text-sm" data-payment-retry>
            <p class="mb-3">{{ ($payment['status'] ?? null) === 'failed' ? __('Thanh toán chưa thành công.') : __('Đơn đang chờ thanh toán.') }}</p>
            @if ($payment['error'] ?? null)<p class="mb-3 text-red-700">{{ $payment['error'] }}</p>@endif
            <form action="{{ vani_route('storefront.order.pay', $order['id']) }}" method="post">
                @csrf
                <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ ($payment['status'] ?? null) === 'failed' ? __('Thanh toán lại') : __('Thanh toán ngay') }}</button>
            </form>
        </section>
    @elseif (($payment['status'] ?? null) === 'pending' && ($payment['action']['type'] ?? null) === 'qr' && ! empty($payment['action']['qr']))
        <section class="mb-6 rounded-[var(--radius-theme)] border border-amber-300 bg-amber-50 p-4 text-sm">
            <h2 class="mb-2 font-semibold">{{ __('Quét mã để thanh toán') }}</h2>
            @if (str_starts_with($payment['action']['qr'], 'https://'))
                <img src="{{ $payment['action']['qr'] }}" alt="{{ __('Mã QR thanh toán đơn :number', ['number' => $order['number']]) }}" class="h-56 w-56">
            @endif
        </section>
    @endif

    @if (($payment['action']['instructions'] ?? null) !== null && ($payment['status'] ?? null) !== 'paid')
        <section class="mb-6 rounded-[var(--radius-theme)] border border-amber-300 bg-amber-50 p-4 text-sm">
            <h2 class="mb-2 font-semibold">{{ __('Hướng dẫn thanh toán') }}</h2>
            <dl class="grid grid-cols-[10rem_1fr] gap-y-1">
                @foreach ($payment['action']['instructions'] as $field => $value)
                    <dt class="text-slate-600">{{ __('storefront::payment.'.$field) }}</dt><dd class="font-medium">{{ $value }}</dd>
                @endforeach
            </dl>
        </section>
    @endif

    <x-vani::hook-slot name="vani.storefront.order.before_summary" :args="[$order]" />
    @include('theme::partials.order-summary')
    @include('theme::partials.order-receipt')
    @include('theme::partials.order-returns')

    <x-vani::hook-slot name="vani.storefront.order.after_summary" :args="[$order]" />
@endsection
