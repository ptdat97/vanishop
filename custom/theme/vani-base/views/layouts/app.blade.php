<!DOCTYPE html>
@php($pageCache = (bool) request()->attributes->get('vani.page_cache'))
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if ($pageCache) data-vani-session="{{ vani_route('storefront.session', ['locale' => app()->getLocale()]) }}" @endif>
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
    <x-vani::hook-slot name="vani.storefront.layout.body_start" />
    <header class="border-b border-slate-200">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 py-4">
            <a href="{{ vani_route('storefront.home') }}" class="text-xl font-semibold text-primary">{{ config('app.name') }}</a>
            <form action="{{ vani_route('storefront.search') }}" method="get" role="search" class="flex-1">
                <label for="q" class="sr-only">Tìm kiếm</label>
                <input id="q" name="q" type="search" value="{{ request()->routeIs('storefront.search') ? request('q') : '' }}" placeholder="Tìm sản phẩm…"
                       class="w-full rounded-[var(--radius-theme)] border border-slate-300 px-3 py-2 text-sm">
            </form>
            @php($headerMenu = ($menus ?? null)?->items('header', app()->getLocale()))
            @php($mobileMenu = ($menus ?? null)?->items('mobile', app()->getLocale()) ?? $headerMenu)
            <nav class="hidden gap-4 text-sm md:flex" aria-label="Menu chính">
                @if ($headerMenu !== null)
                    @include('theme::partials.menu', ['items' => $headerMenu])
                @else
                    <a href="{{ vani_route('storefront.brands') }}">Thương hiệu</a>
                @endif
                <x-vani::hook-slot name="vani.storefront.header.nav" />
            </nav>
            <details class="w-full text-sm md:hidden">
                <summary class="cursor-pointer select-none py-1">Danh mục</summary>
                <nav aria-label="Menu di động" class="mt-2 space-y-1">
                    @if ($mobileMenu !== null)
                        @include('theme::partials.menu-list', ['items' => $mobileMenu])
                    @else
                        <a href="{{ vani_route('storefront.brands') }}" class="block py-1">Thương hiệu</a>
                    @endif
                    <x-vani::hook-slot name="vani.storefront.header.nav" />
                </nav>
            </details>
            <div class="flex items-center gap-4 text-sm">
                <x-vani::hook-slot name="vani.storefront.header.actions" />
                {{-- Trang cache được: luôn render như khách vãng lai; JS (/_vani/phien) cập nhật nhãn + số món. --}}
                <a href="{{ request()->attributes->has('customer') ? vani_route('storefront.account') : vani_route('storefront.account.login') }}" data-vani-account>{{ request()->attributes->has('customer') ? 'Tài khoản' : 'Đăng nhập' }}</a>
                <a href="{{ vani_route('storefront.cart') }}">Giỏ hàng <span data-vani-cart-count hidden class="rounded-full bg-primary px-1.5 text-xs text-white"></span></a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if ($pageCache)
            <p role="status" class="mb-4 rounded-[var(--radius-theme)] bg-emerald-50 px-4 py-2 text-sm text-emerald-800" data-vani-flash="status" hidden></p>
            <p role="alert" class="mb-4 rounded-[var(--radius-theme)] bg-red-50 px-4 py-2 text-sm text-red-700" data-vani-error-for="business" hidden></p>
        @else
            @if (session('status'))
                <p role="status" class="mb-4 rounded-[var(--radius-theme)] bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</p>
            @endif
            @error('business')
                <p role="alert" class="mb-4 rounded-[var(--radius-theme)] bg-red-50 px-4 py-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        @endif

        @yield('content')
    </main>

    <footer class="mt-12 border-t border-slate-200">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-6 text-sm text-slate-500 md:grid-cols-4">
            @php($footerMenus = collect(['footer_1', 'footer_2', 'footer_3', 'footer_4'])->mapWithKeys(fn ($location) => [$location => ($menus ?? null)?->items($location, app()->getLocale())])->filter(fn ($items) => $items !== null))
            @forelse ($footerMenus as $location => $items)
                <nav class="space-y-1" aria-label="{{ $location }}" data-menu="{{ $location }}">
                    @include('theme::partials.menu-list', ['items' => $items])
                </nav>
            @empty
                <nav class="space-y-1" aria-label="Hỗ trợ"><a href="{{ vani_route('storefront.track') }}" class="block">Tra cứu đơn hàng</a></nav>
            @endforelse
            <x-vani::hook-slot name="vani.storefront.footer.columns" />
            <p class="md:col-span-4">© {{ date('Y') }} {{ config('app.name') }}</p>
        </div>
    </footer>

    {{-- Thay đổi giỏ của lần gửi trước → sự kiện `vani:cart-updated` (app.js). Trang có cache lấy qua /_vani/phien. --}}
    @if (! $pageCache && session()->has(\Modules\Storefront\Http\Controllers\Web\CartController::CHANGE_FLASH))
        <script type="application/json" data-vani-cart-change>@json(session(\Modules\Storefront\Http\Controllers\Web\CartController::CHANGE_FLASH))</script>
    @endif
    <x-vani::hook-slot name="vani.storefront.layout.body_end" />
</body>
</html>
