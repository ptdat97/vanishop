@extends('theme::layouts.app')

@section('title', 'Đơn '.$order['number'])

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-2 text-2xl font-semibold">Đơn {{ $order['number'] }}</h1>
            <p class="mb-6 text-sm text-slate-500">{{ $order['placed_at'] }} · {{ $order['status']['label'] ?? $order['order_status'] }}</p>
            @include('theme::partials.order-summary')
            <x-vani::hook-slot name="vani.storefront.account.order_detail" :args="[$order]" />
            @include('theme::partials.order-returns')

            @if ($order['can_cancel'])
                <form action="{{ route('storefront.account.order.cancel', $order['id']) }}" method="post" class="mt-8 flex flex-wrap items-end gap-2">
                    @csrf
                    <div class="flex-1">
                        <label for="reason" class="block text-sm">Lý do huỷ</label>
                        <input id="reason" name="reason" required maxlength="255" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="rounded border border-red-300 px-4 py-2 text-sm text-red-700">Huỷ đơn</button>
                </form>
            @endif
        </section>
    </div>
@endsection
