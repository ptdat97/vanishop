<nav aria-label="{{ __('theme::common.account') }}" class="space-y-1 text-sm" data-vani-account-menu-links>
    {{-- $accountLinks: trang tài khoản của Core, Core dựng sẵn (URL, đang mở) — AccountMenuComposer. --}}
    @foreach ($accountLinks as $link)
        <a href="{{ $link['url'] }}" @class(['block rounded px-3 py-2', 'bg-slate-100 font-semibold' => $link['active']])>{{ $link['label'] }}</a>
    @endforeach
    @foreach ($accountMenu ?? [] as $item)
        {{-- Nhãn của plugin là khoá dịch của plugin (`vani-<plugin>::nhóm.khoá`, ADR-049) — hiện theo ngôn ngữ trang. --}}
        <a href="{{ $item['url'] }}" class="block rounded px-3 py-2" data-account-page="{{ $item['key'] }}">{{ __($item['label']) }}</a>
    @endforeach
    <x-vani::hook-slot name="vani.storefront.account.menu" />
    <form action="{{ vani_route('storefront.account.logout') }}" method="post" class="pt-2">
        @csrf
        <button type="submit" class="px-3 py-2 text-slate-500 underline">{{ __('theme::account.sign_out') }}</button>
    </form>
</nav>
