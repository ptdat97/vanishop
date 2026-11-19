@extends('theme::layouts.app')

@section('title', __('theme::account.sign_up'))

@section('content')
    <div class="mx-auto max-w-sm">
        <h1 class="mb-6 text-2xl font-semibold">{{ __('theme::account.sign_up') }}</h1>
        @if ($sentTo)
            <div role="status" class="space-y-2 rounded-[var(--radius-theme)] border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                <p class="font-medium">{{ __('theme::account.check_inbox') }}</p>
                <p>{{ __('theme::account.verification_sent', ['email' => $sentTo]) }}</p>
                <p class="text-emerald-800">{{ __('theme::account.email_not_received_hint') }}</p>
            </div>
        @else
            <form action="{{ vani_route('storefront.account.register.store') }}" method="post" class="space-y-3">
                @csrf
                @error('business')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                <div>
                    <label for="full_name" class="block text-sm">{{ __('theme::common.full_name') }}</label>
                    <input id="full_name" name="full_name" type="text" autocomplete="name" value="{{ old('full_name') }}" required maxlength="120" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('full_name')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('email')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm">{{ __('theme::account.password_at_least_8_characters') }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('password')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm">{{ __('theme::account.confirm_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                </div>
                <button type="submit" class="w-full rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::account.sign_up') }}</button>
            </form>
        @endif
        <p class="mt-4 text-center text-sm">{{ __('theme::account.already_have_account') }} <a href="{{ vani_route('storefront.account.login') }}" class="font-medium underline">{{ __('theme::account.sign_in') }}</a></p>
    </div>
@endsection
