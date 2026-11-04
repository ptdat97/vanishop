<nav aria-label="{{ __('Tài khoản') }}" class="space-y-1 text-sm">
    @foreach ([['storefront.account', __('Tổng quan')], ['storefront.account.orders', __('Đơn hàng')], ['storefront.account.addresses', __('Địa chỉ')], ['storefront.account.profile', __('Hồ sơ')]] as [$route, $label])
        <a href="{{ route($route) }}" @class(['block rounded px-3 py-2', 'bg-slate-100 font-semibold' => request()->routeIs($route)])>{{ $label }}</a>
    @endforeach
    @foreach ($accountMenu ?? [] as $item)
        <a href="{{ $item['url'] }}" class="block rounded px-3 py-2" data-account-page="{{ $item['key'] }}">{{ $item['label'] }}</a>
    @endforeach
    <x-vani::hook-slot name="vani.storefront.account.menu" />
    <form action="{{ vani_route('storefront.account.logout') }}" method="post" class="pt-2">
        @csrf
        <button type="submit" class="px-3 py-2 text-slate-500 underline">{{ __('Đăng xuất') }}</button>
    </form>
</nav>
