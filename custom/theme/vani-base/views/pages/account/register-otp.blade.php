@extends('theme::layouts.app')

@section('title', __('theme::account.register_otp_title'))

@section('content')
    <div class="mx-auto max-w-sm">
        <h1 class="mb-2 text-2xl font-semibold">{{ __('theme::account.register_otp_title') }}</h1>
        <p class="mb-6 text-sm text-slate-600">{{ __('theme::account.register_otp_sent', ['phone' => $phone]) }}</p>
        <form action="{{ vani_route('storefront.account.register.otp.verify') }}" method="post" class="space-y-3">
            @csrf
            <div>
                <label for="code" class="block text-sm">{{ __('theme::account.register_otp_code') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required maxlength="10" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 tracking-widest">
                @error('code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::account.register_otp_submit') }}</button>
        </form>
        <p class="mt-4 text-center text-sm"><a href="{{ vani_route('storefront.account.register') }}" class="underline">{{ __('theme::account.register_otp_restart') }}</a></p>
    </div>
@endsection
