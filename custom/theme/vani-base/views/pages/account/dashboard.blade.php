@extends('theme::layouts.app')

@section('title', 'Tài khoản')

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-2 text-2xl font-semibold">Xin chào{{ $customer->fullName ? ', '.$customer->fullName : '' }}</h1>
            <p class="mb-6 text-sm text-slate-500">{{ $customer->phone }}@if ($customer->email) · {{ $customer->email }}@endif</p>
            <x-vani::hook-slot name="vani.storefront.account.dashboard" :args="[$customer]" />

            <h2 class="mb-3 mt-6 font-semibold">Đơn hàng gần đây</h2>
            @include('theme::pages.account._order-list', ['orders' => $recentOrders])
        </section>
    </div>
@endsection
