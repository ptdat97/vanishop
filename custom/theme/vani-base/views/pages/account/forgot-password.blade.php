@extends('theme::layouts.app')

@section('title', __('theme::account.forgot_password'))

@section('content')
    <div class="mx-auto max-w-sm">
        <h1 class="mb-2 text-2xl font-semibold">{{ __('theme::account.forgot_password') }}</h1>
        <p class="mb-6 text-sm text-slate-600">{{ __('theme::account.forgot_password_intro') }}</p>
        <form action="{{ vani_route('storefront.account.password.email') }}" method="post" class="space-y-3">
            @csrf
            <div>
                <label for="email" class="block text-sm">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                @error('email')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::account.send_reset_link') }}</button>
        </form>
        <p class="mt-4 text-center text-sm"><a href="{{ vani_route('storefront.account.login') }}" class="underline">{{ __('theme::account.back_sign') }}</a></p>
    </div>
@endsection
