@extends('theme::layouts.app')

@section('title', __('theme::checkout.checkout'))

@section('content')
    <h1 class="mb-6 text-3xl font-medium tracking-tight">{{ __('theme::checkout.checkout') }}</h1>

    {{-- $form (CheckoutForm): values = giá trị từng ô (lần gửi trước hoặc điền sẵn từ tài khoản), saved_addresses, errors = tóm tắt lỗi. --}}
    @php($values = $form['values'])
    @if ($form['errors'] !== [])
        <div id="checkout-errors" role="alert" tabindex="-1" class="mb-6 rounded-[var(--radius-theme)] border border-red-200 bg-red-50 p-4 text-sm text-red-700" data-checkout-errors>
            <p class="font-semibold">{{ __('theme::checkout.order_was_not_placed') }}</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach ($form['errors'] as $error)
                    <li>@if ($error['target'])<a href="#{{ $error['target'] }}" class="underline">{{ $error['message'] }}</a>@else{{ $error['message'] }}@endif</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ vani_route('storefront.checkout.store') }}" method="post" class="grid gap-8 pb-24 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] lg:pb-0" data-checkout-form>
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
        <input type="hidden" name="expected_total" value="{{ $quote['total']['amount'] }}">

        <div class="space-y-8">
            <x-vani::hook-slot name="vani.storefront.checkout.contact_before" :args="[$quote]" />
            <fieldset class="space-y-3">
                <legend class="mb-2 font-semibold">{{ __('theme::common.recipient') }}</legend>
                @if (count($form['saved_addresses']) > 1)
                    {{-- app.js hiện ô này và điền các ô theo địa chỉ chọn; form đã điền sẵn địa chỉ mặc định. --}}
                    <div hidden data-checkout-saved-address-wrap>
                        <label for="checkout-saved-address" class="block text-sm">{{ __('theme::checkout.saved_address') }}</label>
                        <select id="checkout-saved-address" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" data-checkout-saved-address>
                            <option value="">{{ __('theme::checkout.choose_saved_address') }}</option>
                            @foreach ($form['saved_addresses'] as $address)
                                <option value="{{ $address['id'] }}" data-fields="{{ json_encode($address['fields'], JSON_UNESCAPED_UNICODE) }}">{{ $address['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @foreach ([['contact.full_name', 'contact[full_name]', __('theme::common.full_name'), 'text', 'name', 'text'], ['contact.phone', 'contact[phone]', __('theme::common.phone_number'), 'tel', 'tel', 'tel'], ['contact.email', 'contact[email]', __('theme::checkout.email_optional'), 'email', 'email', 'email']] as [$key, $name, $label, $type, $autocomplete, $inputmode])
                    <div>
                        <label for="{{ $key }}" class="block text-sm">{{ $label }}</label>
                        <input id="{{ $key }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" inputmode="{{ $inputmode }}" value="{{ $values[$key] ?? '' }}" @error($key) aria-invalid="true" aria-describedby="{{ $key }}-error" @enderror class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error($key)<p id="{{ $key }}-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </fieldset>
            <x-vani::hook-slot name="vani.storefront.checkout.contact_after" :args="[$quote]" />

            <fieldset class="space-y-3">
                <legend class="mb-2 font-semibold">{{ __('theme::common.delivery_address') }}</legend>
                @if ($provinces !== null)
                    <div>
                        <label for="province_code" class="block text-sm">{{ __('theme::common.province_city') }}</label>
                        <select id="province_code" name="shipping_address[province_code]" autocomplete="address-level1" @error('shipping_address.province_code') aria-invalid="true" aria-describedby="province_code-error" @enderror data-province-select data-wards-url="{{ url('/api/storefront/v1/address/provinces') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">{{ __('theme::common.select_province') }}</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province['code'] }}" @selected(($values['shipping_address.province_code'] ?? '') === $province['code'])>{{ $province['name'] }}</option>
                            @endforeach
                        </select>
                        @error('shipping_address.province_code')<p id="province_code-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ward_code" class="block text-sm">{{ __('theme::common.ward') }}</label>
                        {{-- Lọc danh sách phường/xã gõ không dấu (app.js hiện ô khi danh sách dài). --}}
                        <input type="search" hidden placeholder="{{ __('theme::common.search_ward_hint') }}" aria-label="{{ __('theme::common.search_ward') }}" aria-controls="ward_code" autocomplete="off" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" data-ward-filter>
                        <select id="ward_code" name="shipping_address[ward_code]" autocomplete="address-level2" @error('shipping_address.ward_code') aria-invalid="true" aria-describedby="ward_code-error" @enderror data-ward-select class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">{{ $wards === [] ? __('theme::common.select_province_first') : __('theme::common.select_ward') }}</option>
                            @foreach ($wards as $ward)
                                <option value="{{ $ward['code'] }}" @selected(($values['shipping_address.ward_code'] ?? '') === $ward['code'])>{{ $ward['name'] }}</option>
                            @endforeach
                        </select>
                        @error('shipping_address.ward_code')<p id="ward_code-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="shipping_address.street_line" class="block text-sm">{{ __('theme::common.street_address') }}</label>
                        <input id="shipping_address.street_line" name="shipping_address[street_line]" type="text" autocomplete="address-line1" value="{{ $values['shipping_address.street_line'] ?? '' }}" @error('shipping_address.street_line') aria-invalid="true" aria-describedby="shipping_address.street_line-error" @enderror class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error('shipping_address.street_line')<p id="shipping_address.street_line-error" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    @foreach ([['shipping_address.province_name', 'shipping_address[province_name]', __('theme::common.province_city')], ['shipping_address.ward_name', 'shipping_address[ward_name]', __('theme::common.ward')], ['shipping_address.street_line', 'shipping_address[street_line]', __('theme::common.street_address')]] as [$key, $name, $label])
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
                <legend class="mb-2 font-semibold">{{ __('theme::checkout.delivery') }}</legend>
                @forelse ($quote['shipping_options'] as $option)
                    <label class="flex items-center gap-2 py-1">
                        <input type="radio" name="shipping_method" value="{{ $option['code'] }}" @checked(($values['shipping_method'] ?? $quote['shipping']['code'] ?? null) === $option['code'])>
                        {{ $option['label'] }} — {{ $option['fee']['formatted'] }}
                    </label>
                @empty
                    <p class="text-sm text-red-600">{{ __('theme::checkout.no_delivery_method') }}</p>
                @endforelse
                @error('shipping_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>
            <x-vani::hook-slot name="vani.storefront.checkout.after_shipping" :args="[$quote]" />

            <fieldset id="checkout-payment" tabindex="-1" data-checkout-payment>
                <legend class="mb-2 font-semibold">{{ __('theme::checkout.checkout') }}</legend>
                @forelse ($quote['payment_methods'] as $method)
                    <label class="flex items-center gap-2 py-1">
                        <input type="radio" name="payment_method" value="{{ $method['code'] }}" @checked(($values['payment_method'] ?? $quote['payment_methods'][0]['code'] ?? null) === $method['code'])>
                        {{ $method['label'] }}
                    </label>
                @empty
                    <p class="text-sm text-red-600">{{ __('theme::checkout.no_payment_method') }}</p>
                @endforelse
                @error('payment_method')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>
            <x-vani::hook-slot name="vani.storefront.checkout.payment_after" :args="[$quote]" />

            <div>
                <label for="note" class="block text-sm">{{ __('theme::checkout.note') }}</label>
                <textarea id="note" name="note" rows="2" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">{{ $values['note'] ?? '' }}</textarea>
            </div>
        </div>

        @include('theme::partials.checkout-summary')

        {{-- Điện thoại: tổng + nút đặt hàng luôn thấy ở đáy màn hình (nút trong khung tóm tắt chỉ hiện từ lg). Đặt cuối form để
             Enter trong ô nhập vẫn bấm nút đầu tiên ("Áp dụng" — tính lại), không đặt hàng ngoài ý muốn. --}}
        <div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-slate-200 bg-white px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] lg:hidden" data-checkout-bar>
            <div class="min-w-0 flex-1 text-sm">
                <span class="block text-slate-500">{{ __('theme::checkout.total') }}</span>
                <span class="text-base font-semibold" data-total>{{ $quote['total']['formatted'] }}</span>
            </div>
            <button type="submit" class="vani-button-primary">{{ __('theme::checkout.place_order') }}</button>
        </div>
    </form>
@endsection
