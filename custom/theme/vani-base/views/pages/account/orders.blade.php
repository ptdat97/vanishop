@extends('theme::layouts.app')

@section('title', 'Đơn hàng của tôi')

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-6 text-2xl font-semibold">Đơn hàng</h1>
            @include('theme::pages.account._order-list', ['orders' => $orders])
            @if ($pages > 1)
                <nav class="mt-6 flex gap-2 text-sm" aria-label="Phân trang">
                    @for ($i = 1; $i <= $pages; $i++)
                        <a href="{{ route('storefront.account.orders', ['page' => $i]) }}" @class(['rounded border px-3 py-1', 'border-primary font-semibold' => $i === $page])>{{ $i }}</a>
                    @endfor
                </nav>
            @endif
        </section>
    </div>
@endsection
