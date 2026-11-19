{{--
    MobileBottomNavigation — 5 mục, icon outline, mục đang mở màu nhấn; nằm cuối khung flex của drawer (không che nội dung),
    chừa vùng an toàn iPhone. $current = home|topics|categories|cart|account. "Chuyên đề": Admin → Giao diện → Đầu & chân
    trang → Thanh điều hướng di động (trống = trang Thương hiệu). Số món giỏ, link tài khoản: app.js cập nhật như ở header.
--}}
@php($tabs = [
    ['key' => 'home', 'label' => __('theme::catalog.home'), 'icon' => 'house', 'url' => vani_route('storefront.home')],
    ['key' => 'topics', 'label' => __('theme::catalog.featured'), 'icon' => 'sparkles', 'url' => $shell['mobile_topics_url']],
    ['key' => 'categories', 'label' => __('theme::common.categories'), 'icon' => 'layout-grid', 'url' => null],
    ['key' => 'cart', 'label' => __('theme::common.cart'), 'icon' => 'shopping-bag', 'url' => vani_route('storefront.cart')],
    ['key' => 'account', 'label' => __('theme::common.account'), 'icon' => 'user-round', 'url' => $shell['account']['url']],
])
@php($cartCount = $customerState['cart']['count'] ?? 0)
<nav aria-label="{{ __('theme::catalog.navigation_bar') }}" class="shrink-0 border-t border-gray-100 bg-white pb-[env(safe-area-inset-bottom)]" data-vani-bottom-nav>
    <ul class="flex h-[54px]">
        @foreach ($tabs as $tab)
            @php($tag = $tab['url'] === null ? 'span' : 'a')
            <li class="flex-1">
                <{{ $tag }} @if ($tab['url'] !== null) href="{{ $tab['url'] }}" @endif @if ($tab['key'] === $current) aria-current="page" @endif
                    @if ($tab['key'] === 'account') data-vani-account @endif
                    class="flex h-full flex-col items-center justify-center gap-1 text-[10px] leading-none text-gray-600 [-webkit-tap-highlight-color:transparent] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary aria-[current=page]:text-accent">
                    <span class="relative block size-[22px]">
                        <i data-lucide="{{ $tab['icon'] }}" class="block size-[22px] stroke-[1.5]" aria-hidden="true"></i>
                        @if ($tab['key'] === 'cart')
                            <span data-vani-cart-count @if ($cartCount === 0) hidden @endif
                                class="absolute -right-2 -top-1 min-w-4 rounded-full bg-accent px-1 text-center text-[10px] leading-4 text-white">{{ $cartCount ?: '' }}</span>
                        @endif
                    </span>
                    <span>{{ $tab['label'] }}</span>
                </{{ $tag }}>
            </li>
        @endforeach
    </ul>
</nav>
