@extends('theme::layouts.app')

@section('title', __('Thanh toán'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Thanh toán') }}</h1>

    {{-- $form (CheckoutForm): values = giá trị từng ô (lần gửi trước hoặc điền sẵn từ tài khoản), saved_addresses, errors = tóm tắt lỗi. --}}
    @php($values = $form['values'])
    @if ($form['errors'] !== [])
        <div id="checkout-errors" role="alert" tabindex="-1" class="mb-6 rounded-[var(--radius-theme)] border border-red-200 bg-red-50 p-4 text-sm text-red-700" data-checkout-errors>
            <p class="font-semibold">{{ __('Chưa đặt được đơn:') }}</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach ($form['errors'] as $error)
                    <li>@if ($error['target'])<a href="#{{ $error['target'] }}" class="underline">{{ $error['message'] }}</a>@else{{ $error['message'] }}@endif</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ vani_route('storefront.checkout.store') }}" method="post" class="grid gap-8 pb-24 md:grid-cols-[1fr_22rem] md:pb-0" data-checkout-form>
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
        <input type="hidden" name="expected_total" value="{{ $quote['total']['amount'] }}">

        <div class="space-y-8">
            <x-vani::hook-slot name="vani.storefront.checkout.contact_before" :args="[$quote]" />
            <fieldset class="space-y-3">
                <legend class="mb-2 font-semibold">{{ __('Người nhận') }}</legend>
                @if (count($form['saved_addresses']) > 1)
                    {{-- Chỉ hiện khi có JS (app.js điền các ô theo địa chỉ chọn); không JS: form đã điền sẵn địa chỉ mặc định. --}}
                    <div hidden data-checkout-saved-address-wrap>
                        <label for="checkout-saved-address" class="block text-sm">{{ __('Địa chỉ đã lưu') }}</label>
                        <select id="checkout-saved-address" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" data-checkout-saved-address>
                            <option value="">{{ __('— Chọn địa chỉ đã lưu —') }}</option>
                            @foreach ($form['saved_addresses'] as $address)
                                <option value="{{ $address['id'] }}" data-fields="{{ json_encode($address['fields'], JSON_UNESCAPED_UNICODE) }}">{{ $address['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @foreach ([['contact.full_name', 'contact[full_name]', __('Họ tên'), 'text', 'name', 'text'], ['contact.phone', 'contact[phone]', __('Số điện thoại'), 'tel', 'tel', 'tel'], ['contact.email', 'contact[email]', __('Email (không bắt buộc)'), 'email', 'email', 'email']] as [$key, $name, $label, $type, $autocomplete, $inputmode])
                    <div>
                        <label for="{{ $key }}" class="block text-sm">{{ $label }}</label>
                        <input id="{{ $key }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" inputmode="{{ $inputmode }}" value="{{ $values[$key] ?? '' }}" @error($key) aria-invalid="true" aria-describedby="{{ $key }}-error" @enderror class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error($key)<p id="{{ $key }}-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </fieldset>
            <x-vani::hook-slot name="vani.storefront.checkout.contact_after" :args="[$quote]" />

            <fieldset class="space-y-3">
                <legend class="mb-2 font-semibold">{{ __('Địa chỉ nhận hàng') }}</legend>
                @if ($provinces !== null)
                    <div>
                        <label for="province_code" class="block text-sm">{{ __('Tỉnh/Thành phố') }}</label>
                        <select id="province_code" name="shipping_address[province_code]" autocomplete="address-level1" @error('shipping_address.province_code') aria-invalid="true" aria-describedby="province_code-error" @enderror data-province-select data-wards-url="{{ url('/api/storefront/v1/address/provinces') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">{{ __('— Chọn tỉnh/thành —') }}</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province['code'] }}" @selected(($values['shipping_address.province_code'] ?? '') === $province['code'])>{{ $province['name'] }}</option>
                            @endforeach
                        </select>
                        @error('shipping_address.province_code')<p id="province_code-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ward_code" class="block text-sm">{{ __('Phường/Xã') }}</label>
                        {{-- Lọc danh sách phường/xã gõ không dấu (app.js hiện ô khi danh sách dài); không JS: chọn trong danh sách. --}}
                        <input type="search" hidden placeholder="{{ __('Tìm phường/xã (gõ không dấu được)') }}" aria-label="{{ __('Tìm phường/xã') }}" aria-controls="ward_code" autocomplete="off" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" data-ward-filter>
                        <select id="ward_code" name="shipping_address[ward_code]" autocomplete="address-level2" @error('shipping_address.ward_code') aria-invalid="true" aria-describedby="ward_code-error" @enderror data-ward-select class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">{{ $wards === [] ? __('— Chọn tỉnh/thành trước —') : __('— Chọn phường/xã —') }}</option>
                            @foreach ($wards as $ward)
                                <option value="{{ $ward['code'] }}" @selected(($values['shipping_address.ward_code'] ?? '') === $ward['code'])>{{ $ward['name'] }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" name="action" value="quote" class="mt-1 text-sm underline">{{ __('Tải danh sách phường/xã') }}</button></noscript>
                        @error('shipping_address.ward_code')<p id="ward_code-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="shipping_address.street_line" class="block text-sm">{{ __('Số nhà, đường') }}</label>
                        <input id="shipping_address.street_line" name="shipping_address[street_line]" type="text" autocomplete="address-line1" value="{{ $values['shipping_address.street_line'] ?? '' }}" @error('shipping_address.street_line') aria-invalid="true" aria-describedby="shipping_address.street_line-error" @enderror class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error('shipping_address.street_line')<p id="shipping_address.street_line-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    @foreach ([['shipping_address.province_name', 'shipping_address[province_name]', __('Tỉnh/Thành phố')], ['shipping_address.ward_name', 'shipping_address[ward_name]', __('Phường/Xã')], ['shipping_address.street_line', 'shipping_address[street_line]', __('Số nhà, đường')]] as [$key, $name, $label])
                        <div>
                            <label for="{{ $key }}" class="block text-sm">{{ $label }}</label>
                            <input id="{{ $key }}" name="{{ $name }}" type="text" value="{{ $values[$key] ?? '' }}" @error($key) aria-invalid="true" aria-describedby="{{ $key }}-error" @enderror class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            @error($key)<p id="{{ $key }}-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                @endif
            </fieldset>
            <x-vani::hook-slot name="vani.storefront.checkout.address_after" :args="[$quote]" />

            <fieldset id="checkout-shipping" tabindex="-1" data-checkout-shipping>
                <legend class="mb-2 font-semibold">{{ __('Giao hàng') }}</legend>
                @forelse ($quote['shipping_options'] as $option)
                    <label class="flex items-center gap-2 py-1">
                        <input type="radio" name="shipping_method" value="{{ $option['code'] }}" @checked(($values['shipping_method'] ?? $quote['shipping']['code'] ?? null) === $option['code'])>
                        {{ $option['label'] }} — {{ $option['fee']['formatted'] }}
                    </label>
                @empty
                    <p class="text-sm text-red-600">{{ __('Chưa có phương thức giao hàng.') }}</p>
                @endforelse
                @error('shipping_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>
            <x-vani::hook-slot name="vani.storefront.checkout.after_shipping" :args="[$quote]" />

            <fieldset id="checkout-payment" tabindex="-1" data-checkout-payment>
                <legend class="mb-2 font-semibold">{{ __('Thanh toán') }}</legend>
                @forelse ($quote['payment_methods'] as $method)
                    <label class="flex items-center gap-2 py-1">
                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" @checked(($values['payment_method'] ?? $quote['payment_methods'][0]['code'] ?? null) === $method['code'])>
                        {{ $method['label'] }}
                    </label>
                @empty
                    <p class="text-sm text-red-600">{{ __('Cửa hàng chưa có phương thức thanh toán nào khả dụng.') }}</p>
                @endforelse
                @error('payment_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>
            <x-vani::hook-slot name="vani.storefront.checkout.payment_after" :args="[$quote]" />

            <div>
                <label for="note" class="block text-sm">{{ __('Ghi chú') }}</label>
                <textarea id="note" name="note" rows="2" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ $values['note'] ?? '' }}</textarea>
            </div>
        </div>

        <aside class="h-fit space-y-3 rounded-[var(--radius-theme)] border border-slate-200 p-4 text-sm" data-checkout-summary>
            <h2 class="font-semibold">{{ __('Đơn hàng') }}</h2>
            <ul class="space-y-1">
                @foreach ($quote['lines'] as $line)
                    <li class="flex justify-between gap-2"><span>{{ $line['name'] }} × {{ $line['quantity'] }}</span><span>{{ $line['total']['formatted'] }}</span></li>
                @endforeach
            </ul>
            <x-vani::hook-slot name="vani.storefront.checkout.order_summary" :args="[$quote]" />

            <div class="flex gap-2">
                <label for="voucher_code" class="sr-only">{{ __('Mã giảm giá') }}</label>
                <input id="voucher_code" name="voucher_code" value="{{ $voucherCode ?? old('voucher_code') }}" placeholder="{{ __('Mã giảm giá') }}" class="flex-1 rounded border border-slate-300 px-2 py-1.5">
                <button type="submit" name="action" value="quote" class="rounded border border-slate-300 px-3">{{ __('Áp dụng') }}</button>
            </div>
            {{-- 0.3.104: mã không dùng được không bị giữ lại (đặt hàng không bị chặn) — chỉ nhắc nhẹ khi khách vừa tự nhập. --}}
            @if ($voucherNotice ?? null)
                <p class="text-slate-500" data-voucher-notice>{{ $voucherNotice }} {{ __('Đơn vẫn đặt được, không kèm mã.') }}</p>
            @endif

            <dl class="space-y-1 border-t border-slate-200 pt-3">
                <div class="flex justify-between"><dt>{{ __('Tạm tính') }}</dt><dd>{{ $quote['subtotal']['formatted'] }}</dd></div>
                @if ($quote['discount']['amount'] > 0)
                    <div class="flex justify-between text-accent"><dt>{{ __('Giảm giá') }}</dt><dd>-{{ $quote['discount']['formatted'] }}</dd></div>
                @endif
                <div class="flex justify-between"><dt>{{ __('Phí giao') }}</dt><dd>{{ $quote['shipping_fee']['formatted'] }}</dd></div>
                <div class="flex justify-between text-base font-semibold"><dt>{{ __('Tổng') }}</dt><dd data-total>{{ $quote['total']['formatted'] }}</dd></div>
                @if ($quote['tax_included']['amount'] > 0)
                    <p class="text-xs text-slate-500">{{ __('Đã gồm thuế :amount', ['amount' => $quote['tax_included']['formatted']]) }}</p>
                @endif
            </dl>

            <x-vani::hook-slot name="vani.storefront.checkout.after_summary" :args="[$quote]" />
            <x-vani::hook-slot name="vani.storefront.checkout.before_submit" :args="[$quote]" />
            <button type="submit" class="hidden w-full rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white md:block">{{ __('Đặt hàng') }}</button>
        </aside>

        {{-- Điện thoại: tổng + nút đặt hàng luôn thấy ở đáy màn hình (nút trong khung tóm tắt chỉ hiện từ md). Đặt cuối form để
             Enter trong ô nhập vẫn bấm nút đầu tiên ("Áp dụng" — tính lại), không đặt hàng ngoài ý muốn. --}}
        <div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-slate-200 bg-white px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] md:hidden" data-checkout-bar>
            <div class="min-w-0 flex-1 text-sm">
                <span class="block text-slate-500">{{ __('Tổng') }}</span>
                <span class="text-base font-semibold" data-total>{{ $quote['total']['formatted'] }}</span>
            </div>
            <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-6 py-3 font-medium text-white">{{ __('Đặt hàng') }}</button>
        </div>
    </form>
@endsection
