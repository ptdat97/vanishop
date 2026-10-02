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

    <ul class="divide-y divide-slate-200 border-y border-slate-200 text-sm">
        @foreach ($order['lines'] as $line)
            <li class="flex justify-between gap-4 py-3">
                <span>{{ $line['name'] }} ({{ $line['color_name'] }} / {{ $line['size_code'] }}) × {{ $line['quantity'] }}@include('theme::partials.line-options', ['options' => $line['options']])</span>
                <span>{{ $line['total']['formatted'] }}</span>
            </li>
        @endforeach
    </ul>
    <dl class="ml-auto mt-4 max-w-xs space-y-1 text-sm">
        <div class="flex justify-between"><dt>Tạm tính</dt><dd>{{ $order['subtotal']['formatted'] }}</dd></div>
        @if ($order['discount']['amount'] > 0)
            <div class="flex justify-between"><dt>Giảm giá</dt><dd>-{{ $order['discount']['formatted'] }}</dd></div>
        @endif
        <div class="flex justify-between"><dt>Phí giao</dt><dd>{{ $order['shipping_fee']['formatted'] }}</dd></div>
        <div class="flex justify-between text-base font-semibold"><dt>Tổng</dt><dd>{{ $order['total']['formatted'] }}</dd></div>
    </dl>

    <x-vani::hook-slot name="vani.storefront.order.after_summary" :args="[$order]" />
@endsection
