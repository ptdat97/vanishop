@extends('theme::layouts.app')

@section('title', $address ? __('theme::account.edit_address') : __('theme::account.add_address'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section class="max-w-lg">
            <h1 class="mb-6 text-2xl font-semibold">{{ $address ? __('theme::account.edit_address') : __('theme::account.add_address') }}</h1>
            <form action="{{ $address ? vani_route('storefront.account.addresses.update', $address['id']) : vani_route('storefront.account.addresses.store') }}" method="post" class="space-y-3">
                @csrf
                @if ($address)
                    @method('PUT')
                @endif
                @foreach ([['label', __('theme::account.label_home_office'), 'text', 'off', false], ['full_name', __('theme::common.recipient'), 'text', 'name', true], ['phone', __('theme::common.phone_number'), 'tel', 'tel', true]] as [$field, $label, $type, $autocomplete, $required])
                    <div>
                        <label for="{{ $field }}" class="block text-sm">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" @required($required) value="{{ old($field, $address[$field] ?? '') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error($field)<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                @if ($provinces !== null)
                    <div>
                        <label for="province_code" class="block text-sm">{{ __('theme::common.province_city') }}</label>
                        <select id="province_code" name="province_code" required data-province-select data-wards-url="{{ url('/api/storefront/v1/address/provinces') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">{{ __('theme::common.select_province') }}</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province['code'] }}" @selected(old('province_code', $address['province_code'] ?? '') === $province['code'])>{{ $province['name'] }}</option>
                            @endforeach
                        </select>
                        @error('province_code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ward_code" class="block text-sm">{{ __('theme::common.ward') }}</label>
                        <input type="search" hidden placeholder="{{ __('theme::common.search_ward_hint') }}" aria-label="{{ __('theme::common.search_ward') }}" aria-controls="ward_code" autocomplete="off" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" data-ward-filter>
                        <select id="ward_code" name="ward_code" required data-ward-select class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">{{ $wards === [] ? __('theme::common.select_province_first') : __('theme::common.select_ward') }}</option>
                            @foreach ($wards as $ward)
                                <option value="{{ $ward['code'] }}" @selected(old('ward_code', $address['ward_code'] ?? '') === $ward['code'])>{{ $ward['name'] }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" name="action" value="reload" formnovalidate class="mt-1 text-sm underline">{{ __('theme::common.load_wards') }}</button></noscript>
                        @error('ward_code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    @foreach ([['province_name', __('theme::common.province_city')], ['ward_name', __('theme::common.ward')]] as [$field, $label])
                        <div>
                            <label for="{{ $field }}" class="block text-sm">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="text" required value="{{ old($field, $address[$field] ?? '') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            @error($field)<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                @endif

                <div>
                    <label for="street_line" class="block text-sm">{{ __('theme::common.street_address') }}</label>
                    <input id="street_line" name="street_line" type="text" autocomplete="address-line1" required value="{{ old('street_line', $address['street_line'] ?? '') }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('street_line')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address['is_default'] ?? false))>
                    {{ __('theme::account.set_as_default_address') }}
                </label>
                <div class="flex gap-3">
                    <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::account.save_address') }}</button>
                    <a href="{{ vani_route('storefront.account.addresses') }}" class="px-4 py-2 underline">{{ __('theme::account.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection
