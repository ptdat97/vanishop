@extends('theme::layouts.app')

@section('title', 'Địa chỉ')

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-6 text-2xl font-semibold">Địa chỉ nhận hàng</h1>
            @forelse ($addresses as $address)
                <div class="mb-3 rounded-[var(--radius-theme)] border border-slate-200 p-4 text-sm">
                    <p class="font-medium">{{ $address['full_name'] ?? '' }} · {{ $address['phone'] ?? '' }}@if (! empty($address['is_default'])) <span class="text-xs text-accent">(mặc định)</span>@endif</p>
                    <p class="text-slate-600">{{ $address['street_line'] ?? '' }}, {{ $address['ward_name'] ?? '' }}, {{ $address['province_name'] ?? '' }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Chưa có địa chỉ. Địa chỉ được lưu khi bạn đặt hàng hoặc thêm qua ứng dụng.</p>
            @endforelse
        </section>
    </div>
@endsection
