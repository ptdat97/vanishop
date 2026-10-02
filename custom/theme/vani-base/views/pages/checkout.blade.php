@extends('theme::layouts.app')

@section('title', 'Thanh toán')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">Thanh toán</h1>

    <form action="{{ route('storefront.checkout.store') }}" method="post" class="grid gap-8 md:grid-cols-[1fr_22rem]">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
        <input type="hidden" name="expected_total" value="{{ $quote['total']['amount'] }}">

        <div class="space-y-8">
            <fieldset class="space-y-3">
                <legend class="mb-2 font-semibold">Người nhận</legend>
                @foreach ([['contact.full_name', 'contact[full_name]', 'Họ tên', 'text', 'name'], ['contact.phone', 'contact[phone]', 'Số điện thoại', 'tel', 'tel'], ['contact.email', 'contact[email]', 'Email (không bắt buộc)', 'email', 'email']] as [$key, $name, $label, $type, $autocomplete])
                    <div>
                        <label for="{{ $key }}" class="block text-sm">{{ $label }}</label>
                        <input id="{{ $key }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" value="{{ old($key) }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error($key)<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </fieldset>

            <fieldset class="space-y-3">
                <legend class="mb-2 font-semibold">Địa chỉ nhận hàng</legend>
                @foreach ([['shipping_address.province_name', 'shipping_address[province_name]', 'Tỉnh/Thành phố'], ['shipping_address.ward_name', 'shipping_address[ward_name]', 'Phường/Xã'], ['shipping_address.street_line', 'shipping_address[street_line]', 'Số nhà, đường']] as [$key, $name, $label])
                    <div>
                        <label for="{{ $key }}" class="block text-sm">{{ $label }}</label>
                        <input id="{{ $key }}" name="{{ $name }}" type="text" value="{{ old($key) }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error($key)<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </fieldset>

            <fieldset>
                <legend class="mb-2 font-semibold">Giao hàng</legend>
                @forelse ($quote['shipping_options'] as $option)
                    <label class="flex items-center gap-2 py-1">
                        <input type="radio" name="shipping_method" value="{{ $option['code'] }}" @checked(old('shipping_method', $quote['shipping']['code'] ?? null) === $option['code'])>
                        {{ $option['label'] }} — {{ $option['fee']['formatted'] }}
                    </label>
                @empty
                    <p class="text-sm text-red-600">Chưa có phương thức giao hàng.</p>
                @endforelse
                @error('shipping_method')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>
            <x-vani::hook-slot name="vani.storefront.checkout.after_shipping" :args="[$quote]" />

            <fieldset>
                <legend class="mb-2 font-semibold">Thanh toán</legend>
                @forelse ($quote['payment_methods'] as $method)
                    <label class="flex items-center gap-2 py-1">
                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" @checked(old('payment_method', $quote['payment_methods'][0]['code'] ?? null) === $method['code'])>
                        {{ $method['label'] }}
                    </label>
                @empty
                    <p class="text-sm text-red-600">Cửa hàng chưa có phương thức thanh toán nào khả dụng.</p>
                @endforelse
                @error('payment_method')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>

            <div>
                <label for="note" class="block text-sm">Ghi chú</label>
                <textarea id="note" name="note" rows="2" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ old('note') }}</textarea>
            </div>
        </div>

        <aside class="h-fit space-y-3 rounded-[var(--radius-theme)] border border-slate-200 p-4 text-sm">
            <h2 class="font-semibold">Đơn hàng</h2>
            <ul class="space-y-1">
                @foreach ($quote['lines'] as $line)
                    <li class="flex justify-between gap-2"><span>{{ $line['name'] }} × {{ $line['quantity'] }}</span><span>{{ $line['total']['formatted'] }}</span></li>
                @endforeach
            </ul>

            <div class="flex gap-2">
                <label for="voucher_code" class="sr-only">Mã giảm giá</label>
                <input id="voucher_code" name="voucher_code" value="{{ old('voucher_code') }}" placeholder="Mã giảm giá" class="flex-1 rounded border border-slate-300 px-2 py-1.5">
                <button type="submit" name="action" value="quote" class="rounded border border-slate-300 px-3">Áp dụng</button>
            </div>
            @foreach ($quote['rejected_vouchers'] as $rejected)
                <p class="text-red-600">Mã {{ $rejected['code'] }} không áp dụng được.</p>
            @endforeach

            <dl class="space-y-1 border-t border-slate-200 pt-3">
                <div class="flex justify-between"><dt>Tạm tính</dt><dd>{{ $quote['subtotal']['formatted'] }}</dd></div>
                @if ($quote['discount']['amount'] > 0)
                    <div class="flex justify-between text-accent"><dt>Giảm giá</dt><dd>-{{ $quote['discount']['formatted'] }}</dd></div>
                @endif
                <div class="flex justify-between"><dt>Phí giao</dt><dd>{{ $quote['shipping_fee']['formatted'] }}</dd></div>
                <div class="flex justify-between text-base font-semibold"><dt>Tổng</dt><dd data-total>{{ $quote['total']['formatted'] }}</dd></div>
                @if ($quote['tax_included']['amount'] > 0)
                    <p class="text-xs text-slate-500">Đã gồm thuế {{ $quote['tax_included']['formatted'] }}</p>
                @endif
            </dl>

            <x-vani::hook-slot name="vani.storefront.checkout.before_submit" :args="[$quote]" />
            <button type="submit" class="w-full rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white">Đặt hàng</button>
        </aside>
    </form>
@endsection
