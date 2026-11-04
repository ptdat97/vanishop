<!DOCTYPE html>
@php($pageCache = (bool) request()->attributes->get('vani.page_cache'))
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if ($pageCache) data-vani-session="{{ vani_route('storefront.session', ['locale' => app()->getLocale()]) }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @hasSection('description')<meta name="description" content="@yield('description')">@endif
    @hasSection('canonical')<link rel="canonical" href="@yield('canonical')">@elseif ($seo['canonical'] ?? null)<link rel="canonical" href="{{ $seo['canonical'] }}">@endif
    @if ($seo['robots'] ?? null)<meta name="robots" content="{{ $seo['robots'] }}">@endif
    @foreach ($seo['hreflang'] ?? [] as $hreflang => $href)<link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">@endforeach
    <style>:root{@foreach ($themeTokens ?? [] as $token => $value)--vani-{{ $token }}:{{ $value }};@endforeach}</style>
    @vite(['custom/theme/vani-base/css/app.css', 'custom/theme/vani-base/js/app.js'])
    @stack('head')
    <x-vani::hook-slot name="vani.storefront.layout.head" />
</head>
<body class="min-h-screen bg-surface font-sans text-slate-900 antialiased">
    <x-vani::hook-slot name="vani.storefront.layout.body_start" />
    {{-- Đầu & chân trang do cửa hàng nhập theo schema `settings` trong theme.json (Admin → Giao diện → Đầu & chân trang). --}}
    @php($headerSettings = $themeSettings['header'] ?? [])
    @php($footerSettings = $themeSettings['footer'] ?? [])
    @if ($headerSettings['announcement'] ?? null)
        <div class="bg-primary px-4 py-2 text-center text-sm text-white" data-vani-announcement>
            @if ($headerSettings['announcement_url'] ?? null)
                <a href="{{ $headerSettings['announcement_url'] }}" class="underline">{{ $headerSettings['announcement'] }}</a>
            @else
                {{ $headerSettings['announcement'] }}
            @endif
        </div>
    @endif
    <header class="border-b border-slate-200">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 py-4">
            <a href="{{ vani_route('storefront.home') }}" class="text-xl font-semibold text-primary">{{ config('app.name') }}</a>
            <form action="{{ vani_route('storefront.search') }}" method="get" role="search" class="relative flex-1"
                  data-vani-suggest="{{ route('api.storefront.v1.search.suggestions') }}" data-locale="{{ app()->getLocale() }}">
                <label for="q" class="sr-only">{{ __('Tìm kiếm') }}</label>
                <input id="q" name="q" type="search" value="{{ request()->routeIs('storefront.search') ? request('q') : '' }}" placeholder="{{ __('Tìm sản phẩm…') }}" @if (($popularSearches ?? []) !== []) list="vani-popular-searches" @endif
                       class="w-full rounded-[var(--radius-theme)] border border-slate-300 px-3 py-2 text-sm">
                {{-- Gợi ý khi gõ (§3.5-4, app.js): sản phẩm, danh mục, thương hiệu từ Storefront API. Không JS: tìm như thường. --}}
                <div data-vani-suggest-box hidden class="absolute inset-x-0 top-full z-30 mt-1 rounded-[var(--radius-theme)] border border-slate-200 bg-white p-2 text-sm shadow-lg"></div>
                {{-- Từ khoá nổi bật (Admin → Cấu hình `storefront.popular_searches`): trình duyệt gợi ý khi ô còn trống. --}}
                @if (($popularSearches ?? []) !== [])
                    <datalist id="vani-popular-searches">@foreach ($popularSearches as $term)<option value="{{ $term }}"></option>@endforeach</datalist>
                @endif
            </form>
            @php($headerMenu = ($menus ?? null)?->items('header', app()->getLocale()))
            @php($mobileMenu = ($menus ?? null)?->items('mobile', app()->getLocale()) ?? $headerMenu)
            <nav class="hidden gap-4 text-sm md:flex" aria-label="{{ __('Menu chính') }}">
                @if ($headerMenu !== null)
                    @include('theme::partials.menu', ['items' => $headerMenu])
                @else
                    <a href="{{ vani_route('storefront.brands') }}">{{ __('Thương hiệu') }}</a>
                @endif
                <x-vani::hook-slot name="vani.storefront.header.nav" />
            </nav>
            <details class="w-full text-sm md:hidden">
                <summary class="cursor-pointer select-none py-1">{{ __('Danh mục') }}</summary>
                <nav aria-label="{{ __('Menu di động') }}" class="mt-2 space-y-1">
                    @if ($mobileMenu !== null)
                        @include('theme::partials.menu-list', ['items' => $mobileMenu])
                    @else
                        <a href="{{ vani_route('storefront.brands') }}" class="block py-1">{{ __('Thương hiệu') }}</a>
                    @endif
                    <x-vani::hook-slot name="vani.storefront.header.nav" />
                </nav>
            </details>
            <div class="flex items-center gap-4 text-sm">
                <x-vani::hook-slot name="vani.storefront.header.actions" />
                @if (count($seo['switcher'] ?? []) > 1)
                    <nav aria-label="{{ __('Ngôn ngữ') }}" class="flex gap-1 text-xs uppercase">
                        @foreach ($seo['switcher'] as $option)
                            <a href="{{ $option['url'] }}" hreflang="{{ $option['locale'] }}" lang="{{ $option['locale'] }}" title="{{ $option['label'] }}" @if ($option['current']) aria-current="true" class="font-semibold" @else class="text-slate-500" @endif>{{ $option['locale'] }}</a>
                        @endforeach
                    </nav>
                @endif
                {{-- Không CDN: render sẵn theo khách. Trang cache CDN: render như khách vãng lai; JS (/_vani/phien) cập nhật nhãn + số món. --}}
                <a href="{{ request()->attributes->has('customer') ? vani_route('storefront.account') : vani_route('storefront.account.login') }}" data-vani-account>{{ request()->attributes->has('customer') ? __('Tài khoản') : __('Đăng nhập') }}</a>
                {{-- Giỏ mini (§3.8): JS điền vài dòng gần nhất + tạm tính từ /_vani/phien; không JS vẫn là link tới giỏ. --}}
                <div class="group relative">
                    <a href="{{ vani_route('storefront.cart') }}">{{ __('Giỏ hàng') }} <span data-vani-cart-count @if (($customerState['cart']['count'] ?? 0) === 0) hidden @endif class="rounded-full bg-primary px-1.5 text-xs text-white">{{ ($customerState['cart']['count'] ?? 0) ?: '' }}</span></a>
                    <div data-vani-mini-cart hidden class="invisible absolute right-0 z-20 mt-2 w-72 rounded-[var(--radius-theme)] border border-slate-200 bg-white p-3 text-sm shadow-lg group-hover:visible group-focus-within:visible">
                        <ul class="divide-y divide-slate-100" data-vani-mini-cart-lines></ul>
                        <p class="mt-2 flex justify-between font-medium"><span>{{ __('Tạm tính') }}</span><span data-vani-mini-cart-subtotal></span></p>
                        <a href="{{ vani_route('storefront.cart') }}" class="mt-2 block text-center underline">{{ __('Xem giỏ hàng') }}</a>
                    </div>
                </div>
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
                <nav class="space-y-1" aria-label="{{ __('Hỗ trợ') }}"><a href="{{ vani_route('storefront.track') }}" class="block">{{ __('Tra cứu đơn hàng') }}</a></nav>
            @endforelse
            <x-vani::hook-slot name="vani.storefront.footer.columns" />
            @php($socials = array_filter(['Facebook' => $footerSettings['facebook_url'] ?? null, 'Instagram' => $footerSettings['instagram_url'] ?? null, 'TikTok' => $footerSettings['tiktok_url'] ?? null, 'YouTube' => $footerSettings['youtube_url'] ?? null, 'Zalo' => $footerSettings['zalo_url'] ?? null]))
            @php($payments = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($footerSettings['payment_methods'] ?? ''))))))
            @if (($footerSettings['contact_phone'] ?? null) || ($footerSettings['contact_email'] ?? null) || ($footerSettings['contact_address'] ?? null) || $socials !== [])
                <address class="space-y-1 not-italic" data-vani-footer-contact>
                    <p class="font-medium text-slate-700">{{ __('Liên hệ') }}</p>
                    @if ($footerSettings['contact_phone'] ?? null)<a href="tel:{{ preg_replace('/[^\d+]/', '', $footerSettings['contact_phone']) }}" class="block">{{ $footerSettings['contact_phone'] }}</a>@endif
                    @if ($footerSettings['contact_email'] ?? null)<a href="mailto:{{ $footerSettings['contact_email'] }}" class="block">{{ $footerSettings['contact_email'] }}</a>@endif
                    @if ($footerSettings['contact_address'] ?? null)<p>{!! nl2br(e($footerSettings['contact_address'])) !!}</p>@endif
                    @if ($socials !== [])
                        <p class="flex flex-wrap gap-3 pt-1">
                            @foreach ($socials as $network => $url)<a href="{{ $url }}" rel="noopener" target="_blank">{{ $network }}</a>@endforeach
                        </p>
                    @endif
                </address>
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3 md:col-span-4">
                <p>{{ ($footerSettings['copyright'] ?? null) ?: '© '.date('Y').' '.config('app.name') }}</p>
                @if ($payments !== [])
                    <ul class="flex flex-wrap gap-2" aria-label="{{ __('Phương thức thanh toán') }}">
                        @foreach ($payments as $payment)<li class="rounded border border-slate-300 px-2 py-0.5 text-xs">{{ $payment }}</li>@endforeach
                    </ul>
                @endif
            </div>
        </div>
    </footer>

    {{-- Thay đổi giỏ của lần gửi trước → sự kiện `vani:cart-updated` (app.js). Trang có cache lấy qua /_vani/phien. --}}
    @if (! $pageCache && session()->has(\Modules\Storefront\Http\Controllers\Web\CartController::CHANGE_FLASH))
        <script type="application/json" data-vani-cart-change>@json(session(\Modules\Storefront\Http\Controllers\Web\CartController::CHANGE_FLASH))</script>
    @endif
    {{-- Chuỗi giao diện cho app.js theo ngôn ngữ trang. --}}
    @php($jsStrings = ['wards_loading' => __('— Đang tải… —'), 'province_first' => __('— Chọn tỉnh/thành trước —'), 'choose_ward' => __('— Chọn phường/xã —'), 'wards_failed' => __('— Không tải được, thử lại —'), 'member_price_from' => __('Giá :group: từ :price'), 'member_price' => __('Giá :group: :price'), 'member' => __('thành viên'), 'suggest_products' => __('Sản phẩm'), 'suggest_categories' => __('Danh mục'), 'suggest_brands' => __('Thương hiệu'), 'suggest_all' => __('Xem tất cả kết quả')])
    <script type="application/json" data-vani-i18n>@json($jsStrings)</script>
    @if ($customerState !== null)
        {{-- Cùng dữ liệu với /_vani/phien (trừ flash — đã render ở trên) để JS dựng giỏ mini, giá thành viên như trang cache CDN. --}}
        <script type="application/json" data-vani-session-state>@json($customerState)</script>
    @endif
    <x-vani::hook-slot name="vani.storefront.layout.body_end" />
</body>
</html>
