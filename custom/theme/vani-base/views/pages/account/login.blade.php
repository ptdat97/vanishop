@extends('theme::layouts.app')

@section('title', 'Đăng nhập')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <div class="mx-auto max-w-sm">
        <h1 class="mb-6 text-2xl font-semibold">Đăng nhập</h1>
        <form action="{{ route('storefront.account.otp') }}" method="post" class="space-y-3">
            @csrf
            <label for="phone" class="block text-sm">Số điện thoại</label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone', $phone) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
            @error('phone')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button type="submit" class="w-full rounded-[var(--radius-theme)] border border-primary px-4 py-2">{{ $phone ? 'Gửi lại mã' : 'Gửi mã xác thực' }}</button>
        </form>

        @if ($phone)
            <form action="{{ route('storefront.account.verify') }}" method="post" class="mt-6 space-y-3">
                @csrf
                <label for="code" class="block text-sm">Mã xác thực gửi tới {{ $phone }}</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required class="w-full rounded border border-slate-300 px-3 py-2">
                @error('code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="w-full rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">Đăng nhập</button>
            </form>
        @endif
    </div>
@endsection
