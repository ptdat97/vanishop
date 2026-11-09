@extends('theme::layouts.app')

@section('title', __('Địa chỉ'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold">{{ __('Địa chỉ nhận hàng') }}</h1>
                <a href="{{ vani_route('storefront.account.addresses.create') }}" class="rounded-[var(--radius-theme)] border border-primary px-4 py-2 text-sm">{{ __('Thêm địa chỉ') }}</a>
            </div>
            @forelse ($addresses as $address)
                <div class="mb-3 rounded-[var(--radius-theme)] border border-slate-200 p-4 text-sm" data-address="{{ $address['id'] }}">
                    <p class="font-medium">
                        @if (! empty($address['label']))<span class="mr-1 rounded bg-slate-100 px-2 py-0.5 text-xs">{{ $address['label'] }}</span>@endif
                        {{ $address['full_name'] ?? '' }} · {{ $address['phone'] ?? '' }}@if (! empty($address['is_default'])) <span class="text-xs text-accent">{{ __('(mặc định)') }}</span>@endif
                    </p>
                    <p class="text-slate-600">{{ $address['street_line'] ?? '' }}, {{ $address['ward_name'] ?? '' }}, {{ $address['province_name'] ?? '' }}</p>
                    <div class="mt-2 flex flex-wrap gap-4">
                        <a href="{{ vani_route('storefront.account.addresses.edit', $address['id']) }}" class="underline">{{ __('Sửa') }}</a>
                        @if (empty($address['is_default']))
                            <form action="{{ vani_route('storefront.account.addresses.default', $address['id']) }}" method="post">
                                @csrf
                                <button type="submit" class="underline">{{ __('Đặt mặc định') }}</button>
                            </form>
                        @endif
                        <form action="{{ vani_route('storefront.account.addresses.destroy', $address['id']) }}" method="post" data-confirm="{{ __('Xoá địa chỉ này?') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 underline">{{ __('Xoá') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('Chưa có địa chỉ. Thêm địa chỉ để đặt hàng nhanh hơn.') }}</p>
            @endforelse
            <x-vani::hook-slot name="vani.storefront.account.addresses" :args="[$addresses]" />
        </section>
    </div>
@endsection
