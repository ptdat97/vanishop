@extends('theme::layouts.app')

@section('title', __('Giỏ hàng'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Giỏ hàng') }}</h1>

    @if ($cart === null || $cart['lines'] === [])
        <p class="text-slate-500">{{ __('Giỏ hàng trống.') }} <a href="{{ vani_route('storefront.home') }}" class="underline">{{ __('Tiếp tục mua sắm') }}</a></p>
    @else
        {{-- Chọn dòng để thanh toán (§3.8): ô chọn thuộc form này qua thuộc tính form=, JS tự gửi khi đổi; không JS: nút "Áp dụng lựa chọn". --}}
        <form id="cart-selection" action="{{ vani_route('storefront.cart.select') }}" method="post" class="mb-3 flex items-center gap-4 text-sm">
            @csrf
            <label class="flex items-center gap-2">
                <input type="checkbox" data-select-all @checked(collect($cart['lines'])->every(fn ($line) => $line['selected'] || in_array('unavailable', $line['issues'], true)))>
                {{ __('Chọn tất cả') }}
            </label>
            <button type="submit" class="underline" data-selection-submit>{{ __('Áp dụng lựa chọn') }}</button>
        </form>
        <ul class="divide-y divide-slate-200 border-y border-slate-200">
            @foreach ($cart['lines'] as $line)
                <li class="flex flex-wrap items-center gap-4 py-4">
                    @php($unavailable = in_array('unavailable', $line['issues'], true))
                    <label class="flex items-center">
                        <span class="sr-only">{{ __('Chọn để thanh toán') }}</span>
                        <input type="checkbox" name="lines[]" value="{{ $line['id'] }}" form="cart-selection" data-select-line @checked($line['selected']) @disabled($unavailable)>
                    </label>
                    <div class="min-w-48 flex-1">
                        @if ($line['product'])
                            <a href="{{ vani_route('storefront.product', $line['product']['slug']) }}" class="font-medium">{{ $line['product']['name'] }}</a>
                            <p class="text-sm text-slate-500">{{ $line['product']['color_name'] }} / {{ $line['product']['size_code'] }} · {{ $line['sku'] }}</p>
                            @include('theme::partials.line-options', ['options' => $line['options']])
                        @else
                            <p class="font-medium text-slate-500">{{ __('Sản phẩm không còn bán') }}</p>
                        @endif
                        @foreach ($line['issues'] as $issue)
                            <p class="text-sm text-red-600">{{ __('storefront::messages.issue_'.$issue) }}</p>
                        @endforeach
                    </div>
                    <form action="{{ vani_route('storefront.cart.update', $line['id']) }}" method="post" class="flex items-center gap-2">
                        @csrf
                        <label for="qty-{{ $line['id'] }}" class="sr-only">{{ __('Số lượng') }}</label>
                        <input id="qty-{{ $line['id'] }}" name="quantity" type="number" min="0" max="20" value="{{ $line['quantity'] }}" data-autosubmit class="w-16 rounded border border-slate-300 px-2 py-1">
                        <button type="submit" class="text-sm underline">{{ __('Cập nhật') }}</button>
                    </form>
                    <p class="w-28 text-right font-medium">{{ $line['line_total']['formatted'] ?? '—' }}</p>
                    <form action="{{ vani_route('storefront.cart.remove', $line['id']) }}" method="post">
                        @csrf
                        <button type="submit" class="text-sm text-slate-500 underline">{{ __('Xoá') }}</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <x-vani::hook-slot name="vani.storefront.cart.after_lines" :args="[$cart]" />

        <div class="mt-6 flex flex-col items-end gap-3">
            <p>{{ __('Tạm tính (:count sản phẩm đã chọn):', ['count' => $cart['selected_count']]) }} <strong>{{ $cart['selected_subtotal']['formatted'] }}</strong></p>
            @if ($cart['checkout_ready'])
                <a href="{{ vani_route('storefront.checkout') }}" class="rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white">{{ __('Thanh toán') }}</a>
            @elseif ($cart['selected_count'] === 0)
                <p class="text-sm text-slate-600">{{ __('Chọn ít nhất một sản phẩm để thanh toán.') }}</p>
            @else
                <p class="text-sm text-red-600">{{ __('Cập nhật các dòng có vấn đề trước khi thanh toán.') }}</p>
            @endif
        </div>
    @endif
@endsection
