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
    {{-- Token in thô (không escape): `&#039;` trong <style> làm hỏng font-family. Giá trị cửa hàng nhập đã lọc ở Themes::tokens() (chặn ;{}<>). --}}
    <style>:root{@foreach ($themeTokens ?? [] as $token => $value)--vani-{!! $token !!}:{!! $value !!};@endforeach}</style>
    {{-- Font mặc định của theme (Anivele, vite.config.js): chỉ nạp khi token `font-sans` còn dùng nó. --}}
    @if (str_contains((string) ($themeTokens['font-sans'] ?? ''), 'Anivele'))
        @fonts('anivele')
    @endif
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
                {{-- Giỏ mini (0.3.93): bấm mở offcanvas bên phải (app.js tải nội dung qua JSON); không JS vẫn là link tới trang giỏ. --}}
                <a href="{{ vani_route('storefront.cart') }}" data-vani-cart-open aria-haspopup="dialog">{{ __('Giỏ hàng') }} <span data-vani-cart-count @if (($customerState['cart']['count'] ?? 0) === 0) hidden @endif class="rounded-full bg-primary px-1.5 text-xs text-white">{{ ($customerState['cart']['count'] ?? 0) ?: '' }}</span></a>
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
    @php($jsStrings = ['wards_loading' => __('— Đang tải… —'), 'province_first' => __('— Chọn tỉnh/thành trước —'), 'choose_ward' => __('— Chọn phường/xã —'), 'wards_failed' => __('— Không tải được, thử lại —'), 'member_price_from' => __('Giá :group: từ :price'), 'member_price' => __('Giá :group: :price'), 'member' => __('thành viên'), 'suggest_products' => __('Sản phẩm'), 'suggest_categories' => __('Danh mục'), 'suggest_brands' => __('Thương hiệu'), 'suggest_all' => __('Xem tất cả kết quả'), 'carousel_prev' => __('Mục trước'), 'carousel_next' => __('Mục sau'), 'lightbox_prev' => __('Ảnh trước'), 'lightbox_next' => __('Ảnh sau'), 'lightbox_close' => __('Đóng'), 'lightbox_zoom' => __('Phóng to'), 'lightbox_error' => __('Không tải được ảnh.'), 'request_failed' => __('Có lỗi xảy ra, vui lòng thử lại.')])
    <script type="application/json" data-vani-i18n>@json($jsStrings)</script>
    {{--
        Giỏ mini offcanvas (0.3.93): <dialog> mở bằng showModal() — khoá nền, giữ focus, Esc đóng. Nội dung = partial
        theme::partials.mini-cart do server render (CartController, JSON `html`).
    --}}
    <dialog class="vani-offcanvas" data-vani-offcanvas-cart aria-labelledby="vani-cart-title">
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h2 id="vani-cart-title" class="text-lg font-semibold">{{ __('Giỏ hàng') }}</h2>
                <button type="button" class="px-2 text-2xl leading-none text-slate-500 hover:text-slate-900" data-cart-close aria-label="{{ __('Đóng') }}">×</button>
            </div>
            <p class="mx-4 mt-3 hidden rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-800" data-vani-cart-message role="status"></p>
            <div class="flex min-h-0 flex-1 flex-col" data-vani-mini-cart-body aria-busy="true">
                <p class="p-4 text-sm text-slate-500">{{ __('Đang tải…') }}</p>
            </div>
        </div>
    </dialog>
    {{-- URL cho window.vani (app.js) theo ngôn ngữ trang; giống nhau với mọi khách nên trang vẫn cache được. --}}
    {{-- `987654321` = chỗ app.js thay bằng id dòng giỏ. --}}
    @php($vaniUrls = [
            'cart' => vani_route('storefront.cart'),
            'cart_add' => vani_route('storefront.cart.add'),
            'cart_line' => vani_route('storefront.cart.update', ['line' => 987654321]),
            'cart_remove' => vani_route('storefront.cart.remove', ['line' => 987654321]),
            'cart_select' => vani_route('storefront.cart.select'),
            'cart_voucher' => vani_route('storefront.cart.voucher'),
            'checkout' => vani_route('storefront.checkout'),
            'login' => vani_route('storefront.account.login'),
            'login_otp' => vani_route('storefront.account.otp'),
            'login_verify' => vani_route('storefront.account.verify'),
            'logout' => vani_route('storefront.account.logout'),
            'search' => vani_route('storefront.search'),
            'search_suggestions' => route('api.storefront.v1.search.suggestions'),
            'session' => vani_route('storefront.session', ['locale' => app()->getLocale()]),
    ])
    <script type="application/json" data-vani-urls>@json($vaniUrls)</script>
    @if ($customerState !== null)
        {{-- Cùng dữ liệu với /_vani/phien (trừ flash — đã render ở trên) để JS dựng giỏ mini, giá thành viên như trang cache CDN. --}}
        <script type="application/json" data-vani-session-state>@json($customerState)</script>
    @endif
    <x-vani::hook-slot name="vani.storefront.layout.body_end" />
</body>
</html>
