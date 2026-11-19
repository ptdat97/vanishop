@extends('theme::layouts.app')

@section('title', __('theme::common.account'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-2 text-2xl font-semibold">{{ $customer->fullName ? __('theme::account.hello_name', ['name' => $customer->fullName]) : __('theme::account.hello') }}</h1>
            <p class="mb-6 text-sm text-slate-500">{{ $customer->phone }}@if ($customer->email) · {{ $customer->email }}@endif</p>
            <x-vani::hook-slot name="vani.storefront.account.dashboard" :args="[$customer]" />

            <h2 class="mb-3 mt-6 font-semibold">{{ __('theme::account.recent_orders') }}</h2>
            @include('theme::pages.account._order-list', ['orders' => $recentOrders])
        </section>
    </div>
@endsection
