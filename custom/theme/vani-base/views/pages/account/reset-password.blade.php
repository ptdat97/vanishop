@extends('theme::layouts.app')

@section('title', __('theme::account.set_new_password'))

@section('content')
    <div class="mx-auto max-w-sm">
        <h1 class="mb-6 text-2xl font-semibold">{{ __('theme::account.set_new_password') }}</h1>
        <form action="{{ vani_route('storefront.account.password.update-forgotten', ['token' => $token]) }}" method="post" class="space-y-3">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            @error('business')
                <p role="alert" class="text-sm text-red-600">{{ $message }} <a href="{{ vani_route('storefront.account.password.forgot') }}" class="underline">{{ __('theme::account.send_new_link') }}</a></p>
            @enderror
            @error('token')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            <div>
                <label for="password" class="block text-sm">{{ __('theme::account.new_password_at_least_8') }}</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                @error('password')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm">{{ __('theme::account.confirm_new_password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::account.save_new_password') }}</button>
        </form>
    </div>
@endsection
