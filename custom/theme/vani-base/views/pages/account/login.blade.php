@extends('theme::layouts.app')

@section('title', __('Đăng nhập'))

@section('content')
    <div class="mx-auto max-w-sm">
        <h1 class="mb-6 text-2xl font-semibold">{{ __('Đăng nhập') }}</h1>
        {{-- app.js gửi JSON (window.vani.auth.login) — không tải lại trang; không JS: form POST như thường. --}}
        <form action="{{ vani_route('storefront.account.authenticate') }}" method="post" class="space-y-3" data-vani-login>
            @csrf
            <div>
                <label for="email" class="block text-sm">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <div>
                <div class="flex items-baseline justify-between">
                    <label for="password" class="block text-sm">{{ __('Mật khẩu') }}</label>
                    <a href="{{ vani_route('storefront.account.password.forgot') }}" class="text-sm underline">{{ __('Quên mật khẩu?') }}</a>
                </div>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <p role="alert" class="text-sm text-red-600" data-vani-form-error @if ($errors->any()) @else hidden @endif>{{ $errors->first('business') ?: $errors->first('email') ?: $errors->first('password') }}</p>
            <button type="submit" class="w-full rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('Đăng nhập') }}</button>
        </form>
        <p class="mt-4 text-center text-sm">{{ __('Chưa có tài khoản?') }} <a href="{{ vani_route('storefront.account.register') }}" class="font-medium underline">{{ __('Đăng ký') }}</a></p>
        {{-- Phương thức đăng nhập khác do plugin thêm (OTP theo số điện thoại, mạng xã hội…). --}}
        <x-vani::hook-slot name="vani.storefront.account.login" />
    </div>
@endsection
