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
    <x-vani::hook-slot name="vani.storefront.layout.head" :wrap="false" />
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
    <x-vani::hook-slot name="vani.storefront.layout.header.before" />
    <header class="border-b border-slate-200 sticky top-0 z-50 bg-white">
        @php($headerMenu = ($menus ?? null)?->items('header', app()->getLocale()))
        @php($mobileMenu = ($menus ?? null)?->items('mobile', app()->getLocale()) ?? $headerMenu)
        <div class="mx-auto grid container grid-cols-[1fr_auto] items-center gap-x-4 gap-y-3 px-4 py-4 md:grid-cols-[1fr_auto_1fr]">
            <a href="{{ vani_route('storefront.home') }}" class="col-start-1 row-start-1 justify-self-start text-xl font-semibold text-primary">{{ config('app.name') }}</a>
            <nav class="hidden md:col-start-2 md:row-start-1 md:flex md:justify-center" aria-label="{{ __('Menu chính') }}">
                @if ($headerMenu !== null)
                    @include('theme::partials.menu', ['items' => $headerMenu])
                @else
                    <a href="{{ vani_route('storefront.brands') }}">{{ __('Thương hiệu') }}</a>
                @endif
                <x-vani::hook-slot name="vani.storefront.header.nav" class="flex flex-wrap items-center gap-4 ml-4" />
            </nav>
            <form action="{{ vani_route('storefront.search') }}" method="get" role="search" class="relative col-span-2 row-start-2 md:col-span-3"
                data-vani-search-fallback data-vani-suggest="{{ route('api.storefront.v1.search.suggestions') }}" data-locale="{{ app()->getLocale() }}">
                <label for="vani-search-fallback-q" class="sr-only">{{ __('Tìm kiếm') }}</label>
                <input id="vani-search-fallback-q" name="q" type="search" value="{{ request()->routeIs('storefront.search') ? request('q') : '' }}"
                    placeholder="{{ __('Tìm sản phẩm…') }}" @if (($popularSearches ?? []) !== []) list="vani-search-fallback-popular-searches" @endif
                    class="w-full rounded-[var(--radius-theme)] border border-slate-300 px-3 py-2 text-sm">
                <div data-vani-suggest-box hidden class="absolute inset-x-0 top-full z-30 mt-1 rounded-[var(--radius-theme)] border border-slate-200 bg-white p-2 text-sm shadow-lg"></div>
                @if (($popularSearches ?? []) !== [])
                    <datalist id="vani-search-fallback-popular-searches">
                        @foreach ($popularSearches as $term)
                            <option value="{{ $term }}"></option>
                        @endforeach
                    </datalist>
                @endif
            </form>
            <div class="col-start-2 row-start-1 flex items-center justify-self-end gap-1 md:col-start-3">
                <button type="button" class="inline-flex size-10 cursor-pointer items-center justify-center rounded text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    data-js-only data-vani-search-open data-vani-modal-open="vani-search-modal" aria-label="{{ __('Tìm kiếm') }}" aria-haspopup="dialog" aria-controls="vani-search-modal">
                    <i data-lucide="search" class="size-5" aria-hidden="true"></i>
                </button>
                <button type="button" class="hidden cursor-pointer items-center justify-center rounded text-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary md:hidden"
                    data-vani-mobile-menu-open data-vani-drawer-open="vani-mobile-menu" aria-label="{{ __('Danh mục') }}" aria-haspopup="dialog" aria-controls="vani-mobile-menu">
                    <span class="sr-only">{{ __('Danh mục') }}</span>
                    <i data-lucide="menu" class="size-5" aria-hidden="true"></i>
                </button>
                <x-vani::hook-slot name="vani.storefront.header.actions" />
                @if (count($seo['switcher'] ?? []) > 1)
                    <nav aria-label="{{ __('Ngôn ngữ') }}" class="hidden gap-1 text-xs uppercase sm:flex">
                        @foreach ($seo['switcher'] as $option)
                            <a href="{{ $option['url'] }}" hreflang="{{ $option['locale'] }}" lang="{{ $option['locale'] }}" title="{{ $option['label'] }}" @if ($option['current']) aria-current="true" class="font-semibold" @else class="text-slate-500" @endif>{{ $option['locale'] }}</a>
                        @endforeach
                    </nav>
                @endif
                {{-- Không CDN: render sẵn theo khách. Trang cache CDN: render như khách vãng lai; JS (/_vani/phien) cập nhật nhãn + số món. --}}
                <a href="{{ request()->attributes->has('customer') ? vani_route('storefront.account') : vani_route('storefront.account.login') }}"
                    class="inline-flex size-10 items-center justify-center rounded text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    data-vani-account aria-label="{{ request()->attributes->has('customer') ? __('Tài khoản') : __('Đăng nhập') }}"
                    title="{{ request()->attributes->has('customer') ? __('Tài khoản') : __('Đăng nhập') }}">
                    <i data-lucide="user-round" class="size-5" aria-hidden="true"></i>
                </a>
                {{-- Giỏ mini (0.3.93): bấm mở offcanvas bên phải (app.js tải nội dung qua JSON); không JS vẫn là link tới trang giỏ. --}}
                <a href="{{ vani_route('storefront.cart') }}" data-vani-cart-open aria-haspopup="dialog"
                    class="relative inline-flex size-10 items-center justify-center rounded text-slate-700 transition-colors hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    aria-label="{{ __('Giỏ hàng') }}" title="{{ __('Giỏ hàng') }}">
                    <i data-lucide="shopping-bag" class="size-5" aria-hidden="true"></i>
                    <span data-vani-cart-count @if (($customerState['cart']['count'] ?? 0) === 0) hidden @endif
                        class="absolute -right-1 -top-1 min-w-5 rounded-full bg-primary px-1.5 text-center text-xs leading-5 text-white">{{ ($customerState['cart']['count'] ?? 0) ?: '' }}</span>
                </a>
            </div>
            <details class="col-span-2 row-start-3 w-full text-sm md:hidden" data-vani-mobile-menu-fallback>
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
        </div>
    </header>
    @include('theme::partials.modal', [
        'id' => 'vani-search-modal',
        'title' => __('Tìm kiếm sản phẩm'),
        'contentView' => 'theme::partials.search-modal-content',
        'dialogClass' => 'vani-search-modal',
        'dataAttributes' => ['data-vani-search-modal' => true],
    ])
    @include('theme::partials.drawer', [
        'id' => 'vani-mobile-menu',
        'title' => __('Danh mục'),
        'contentView' => 'theme::partials.mobile-menu-content',
        'contentData' => [
            'items' => $mobileMenu ?? [],
            'fallbackUrl' => vani_route('storefront.brands'),
        ],
        'dialogClass' => 'vani-mobile-menu',
        'dataAttributes' => ['data-vani-mobile-menu-dialog' => true],
    ])
    <x-vani::hook-slot name="vani.storefront.layout.header.after" />

    <main class="mx-auto container px-4 py-6">
        <x-vani::hook-slot name="vani.storefront.layout.main.before" />
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
        <x-vani::hook-slot name="vani.storefront.layout.main.after" />
    </main>

    <footer class="mt-12 border-t border-slate-200">
        <div class="mx-auto grid container gap-6 px-4 py-6 text-sm text-slate-500 md:grid-cols-4">
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
    <x-vani::hook-slot name="vani.storefront.layout.footer.after" />

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
    @include('theme::partials.drawer', [
        'id' => 'vani-cart-drawer',
        'title' => __('Giỏ hàng'),
        'contentView' => 'theme::partials.cart-drawer-content',
        'dialogClass' => 'vani-offcanvas',
        'dataAttributes' => ['data-vani-offcanvas-cart' => true],
        'closeAttributes' => ['data-cart-close' => true],
    ])
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
