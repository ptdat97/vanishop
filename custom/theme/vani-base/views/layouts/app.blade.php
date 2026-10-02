<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @hasSection('description')<meta name="description" content="@yield('description')">@endif
    @hasSection('canonical')<link rel="canonical" href="@yield('canonical')">@endif
    <style>:root{@foreach ($themeTokens ?? [] as $token => $value)--vani-{{ $token }}:{{ $value }};@endforeach}</style>
    @vite(['custom/theme/vani-base/css/app.css', 'custom/theme/vani-base/js/app.js'])
    @stack('head')
    <x-vani::hook-slot name="vani.storefront.layout.head" />
</head>
<body class="min-h-screen bg-surface font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 py-4">
            <a href="{{ route('storefront.home') }}" class="text-xl font-semibold text-primary">{{ config('app.name') }}</a>
            <form action="{{ route('storefront.search') }}" method="get" role="search" class="flex-1">
                <label for="q" class="sr-only">Tìm kiếm</label>
                <input id="q" name="q" type="search" value="{{ request()->routeIs('storefront.search') ? request('q') : '' }}" placeholder="Tìm sản phẩm…"
                       class="w-full rounded-[var(--radius-theme)] border border-slate-300 px-3 py-2 text-sm">
            </form>
            <nav class="flex gap-4 text-sm">
                <a href="{{ route('storefront.brands') }}">Thương hiệu</a>
                <a href="{{ route('storefront.cart') }}">Giỏ hàng</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('status'))
            <p role="status" class="mb-4 rounded-[var(--radius-theme)] bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif
        @error('business')
            <p role="alert" class="mb-4 rounded-[var(--radius-theme)] bg-red-50 px-4 py-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        @yield('content')
    </main>

    <footer class="mt-12 border-t border-slate-200">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-slate-500">© {{ date('Y') }} {{ config('app.name') }}</div>
    </footer>

    <x-vani::hook-slot name="vani.storefront.layout.body_end" />
</body>
</html>
