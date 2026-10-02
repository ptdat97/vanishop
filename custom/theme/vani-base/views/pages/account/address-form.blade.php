@extends('theme::layouts.app')

@section('title', $address ? 'Sửa địa chỉ' : 'Thêm địa chỉ')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section class="max-w-lg">
            <h1 class="mb-6 text-2xl font-semibold">{{ $address ? 'Sửa địa chỉ' : 'Thêm địa chỉ' }}</h1>
            <form action="{{ $address ? route('storefront.account.addresses.update', $address['id']) : route('storefront.account.addresses.store') }}" method="post" class="space-y-3">
                @csrf
                @if ($address)
                    @method('PUT')
                @endif
                @foreach ([['label', 'Tên gợi nhớ (Nhà, Công ty…)', 'text', 'off', false], ['full_name', 'Người nhận', 'text', 'name', true], ['phone', 'Số điện thoại', 'tel', 'tel', true]] as [$field, $label, $type, $autocomplete, $required])
                    <div>
                        <label for="{{ $field }}" class="block text-sm">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" @required($required) value="{{ old($field, $address[$field] ?? '') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error($field)<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                @if ($provinces !== null)
                    <div>
                        <label for="province_code" class="block text-sm">Tỉnh/Thành phố</label>
                        <select id="province_code" name="province_code" required data-province-select data-wards-url="{{ url('/api/storefront/v1/address/provinces') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">— Chọn tỉnh/thành —</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province['code'] }}" @selected(old('province_code', $address['province_code'] ?? '') === $province['code'])>{{ $province['name'] }}</option>
                            @endforeach
                        </select>
                        @error('province_code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ward_code" class="block text-sm">Phường/Xã</label>
                        <select id="ward_code" name="ward_code" required data-ward-select class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">{{ $wards === [] ? '— Chọn tỉnh/thành trước —' : '— Chọn phường/xã —' }}</option>
                            @foreach ($wards as $ward)
                                <option value="{{ $ward['code'] }}" @selected(old('ward_code', $address['ward_code'] ?? '') === $ward['code'])>{{ $ward['name'] }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" name="action" value="reload" formnovalidate class="mt-1 text-sm underline">Tải danh sách phường/xã</button></noscript>
                        @error('ward_code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    @foreach ([['province_name', 'Tỉnh/Thành phố'], ['ward_name', 'Phường/Xã']] as [$field, $label])
                        <div>
                            <label for="{{ $field }}" class="block text-sm">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="text" required value="{{ old($field, $address[$field] ?? '') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            @error($field)<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                @endif

                <div>
                    <label for="street_line" class="block text-sm">Số nhà, đường</label>
                    <input id="street_line" name="street_line" type="text" autocomplete="address-line1" required value="{{ old('street_line', $address['street_line'] ?? '') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('street_line')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address['is_default'] ?? false))>
                    Đặt làm địa chỉ mặc định
                </label>
                <div class="flex gap-3">
                    <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">Lưu địa chỉ</button>
                    <a href="{{ route('storefront.account.addresses') }}" class="px-4 py-2 underline">Huỷ</a>
                </div>
            </form>
        </section>
    </div>
@endsection
