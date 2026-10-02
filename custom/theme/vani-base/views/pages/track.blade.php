@extends('theme::layouts.app')

@section('title', 'Tra cứu đơn hàng')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Tra cứu đơn hàng</h1>
    <form action="{{ route('storefront.track.search') }}" method="post" class="mb-8 grid max-w-xl gap-3 sm:grid-cols-[1fr_1fr_auto]">
        @csrf
        <div>
            <label for="number" class="block text-sm">Mã đơn</label>
            <input id="number" name="number" value="{{ old('number', request('number')) }}" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label for="track-phone" class="block text-sm">Số điện thoại đặt hàng</label>
            <input id="track-phone" name="phone" type="tel" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <button type="submit" class="self-end rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">Tra cứu</button>
    </form>

    @if (! empty($notFound))
        <p role="alert" class="text-sm text-red-600">Không tìm thấy đơn với mã và số điện thoại này.</p>
    @elseif ($order)
        <h2 class="mb-2 font-semibold">Đơn {{ $order['number'] }} · {{ $order['status']['label'] ?? $order['order_status'] }}</h2>
        @include('theme::partials.order-summary')
    @endif
@endsection
