@extends('theme::layouts.app')

@section('title', __('theme::account.delete_account'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section class="max-w-lg">
            <h1 class="mb-4 text-2xl font-semibold">{{ __('theme::account.delete_account') }}</h1>
            <div class="mb-6 space-y-2 text-sm text-slate-600">
                <p>{{ __('theme::account.deletion_effects') }}</p>
                <p>{{ __('theme::account.deletion_orders_kept') }}</p>
            </div>

            @if ($blockers !== [])
                <div role="alert" class="rounded-[var(--radius-theme)] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-medium">{{ __('theme::account.deletion_blocked_intro') }}</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($blockers as $blocker)
                            <li>{{ $blocker['label'] }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2">{{ __('theme::account.deletion_wait_hint') }} <a href="{{ vani_route('storefront.account.orders') }}" class="underline">{{ __('theme::account.view_orders') }}</a>.</p>
                </div>
            @elseif (! $customer->hasPassword)
                {{-- Tài khoản chưa có mật khẩu (OTP/mạng xã hội): xác nhận bằng mã một lần gửi qua email hoặc SMS. --}}
                @error('business')<p role="alert" class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror
                @if ($codeSent)
                    <form action="{{ vani_route('storefront.account.delete.confirm') }}" method="post" class="space-y-3" data-deletion-code-form>
                        @csrf
                        <div>
                            <label for="code" class="block text-sm">{{ __('theme::account.enter_deletion_code') }}</label>
                            <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            @error('code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="rounded-[var(--radius-theme)] bg-red-600 px-4 py-2 font-medium text-white">{{ __('theme::account.permanently_delete_my_account') }}</button>
                            <a href="{{ vani_route('storefront.account.profile') }}" class="text-sm underline">{{ __('theme::account.no_keep_my_account') }}</a>
                        </div>
                    </form>
                @endif
                <form action="{{ vani_route('storefront.account.delete.code') }}" method="post" class="{{ $codeSent ? 'mt-4' : '' }}">
                    @csrf
                    @unless ($codeSent)<p class="mb-3 text-sm">{{ __('theme::account.deletion_code_intro') }}</p>@endunless
                    <button type="submit" class="{{ $codeSent ? 'text-sm underline' : 'rounded-[var(--radius-theme)] border border-slate-300 px-4 py-2 text-sm font-medium' }}">{{ $codeSent ? __('theme::account.resend_deletion_code') : __('theme::account.send_deletion_code') }}</button>
                </form>
            @else
                <form action="{{ vani_route('storefront.account.delete.confirm') }}" method="post" class="space-y-3">
                    @csrf
                    @error('business')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <div>
                        <label for="password" class="block text-sm">{{ __('theme::account.enter_password_confirm') }}</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error('password')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-[var(--radius-theme)] bg-red-600 px-4 py-2 font-medium text-white">{{ __('theme::account.permanently_delete_my_account') }}</button>
                        <a href="{{ vani_route('storefront.account.profile') }}" class="text-sm underline">{{ __('theme::account.no_keep_my_account') }}</a>
                    </div>
                </form>
            @endif
        </section>
    </div>
@endsection
