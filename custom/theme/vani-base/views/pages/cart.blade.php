@extends('theme::layouts.app')

@section('title', 'Giỏ hàng')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Giỏ hàng</h1>

    @if ($cart === null || $cart['lines'] === [])
        <p class="text-slate-500">Giỏ hàng trống. <a href="{{ route('storefront.home') }}" class="underline">Tiếp tục mua sắm</a></p>
    @else
        <ul class="divide-y divide-slate-200 border-y border-slate-200">
            @foreach ($cart['lines'] as $line)
                <li class="flex flex-wrap items-center gap-4 py-4">
                    <div class="min-w-48 flex-1">
                        @if ($line['product'])
                            <a href="{{ route('storefront.product', $line['product']['slug']) }}" class="font-medium">{{ $line['product']['name'] }}</a>
                            <p class="text-sm text-slate-500">{{ $line['product']['color_name'] }} / {{ $line['product']['size_code'] }} · {{ $line['sku'] }}</p>
                            @include('theme::partials.line-options', ['options' => $line['options']])
                        @else
                            <p class="font-medium text-slate-500">Sản phẩm không còn bán</p>
                        @endif
                        @foreach ($line['issues'] as $issue)
                            <p class="text-sm text-red-600">{{ __('storefront::messages.issue_'.$issue) }}</p>
                        @endforeach
                    </div>
                    <form action="{{ route('storefront.cart.update', $line['id']) }}" method="post" class="flex items-center gap-2">
                        @csrf
                        <label for="qty-{{ $line['id'] }}" class="sr-only">Số lượng</label>
                        <input id="qty-{{ $line['id'] }}" name="quantity" type="number" min="0" max="20" value="{{ $line['quantity'] }}" data-autosubmit class="w-16 rounded border border-slate-300 px-2 py-1">
                        <button type="submit" class="text-sm underline">Cập nhật</button>
                    </form>
                    <p class="w-28 text-right font-medium">{{ $line['line_total']['formatted'] ?? '—' }}</p>
                    <form action="{{ route('storefront.cart.remove', $line['id']) }}" method="post">
                        @csrf
                        <button type="submit" class="text-sm text-slate-500 underline">Xoá</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <x-vani::hook-slot name="vani.storefront.cart.after_lines" :args="[$cart]" />

        <div class="mt-6 flex flex-col items-end gap-3">
            <p>Tạm tính: <strong>{{ $cart['subtotal']['formatted'] }}</strong></p>
            @if ($cart['checkout_ready'])
                <a href="{{ route('storefront.checkout') }}" class="rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white">Thanh toán</a>
            @else
                <p class="text-sm text-red-600">Cập nhật các dòng có vấn đề trước khi thanh toán.</p>
            @endif
        </div>
    @endif
@endsection
