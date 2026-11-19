@extends('theme::layouts.app')

@section('title', __('theme::account.my_orders'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-6 text-2xl font-semibold">{{ __('theme::common.orders') }}</h1>
            @include('theme::pages.account._order-list', ['orders' => $orders])
            @if ($pages > 1)
                <nav class="mt-6 flex gap-2 text-sm" aria-label="{{ __('theme::common.pagination') }}">
                    @for ($i = 1; $i <= $pages; $i++)
                        <a href="{{ vani_route('storefront.account.orders', ['page' => $i]) }}" @class(['rounded border px-3 py-1', 'border-primary font-semibold' => $i === $page])>{{ $i }}</a>
                    @endfor
                </nav>
            @endif
        </section>
    </div>
@endsection
