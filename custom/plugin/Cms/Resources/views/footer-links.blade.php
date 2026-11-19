<nav aria-label="{{ __('vani-cms::storefront.information') }}" class="text-sm">
    <h2 class="mb-2 font-semibold">{{ __('vani-cms::storefront.information') }}</h2>
    <ul class="space-y-1">
        @foreach ($links as $link)
            <li><a href="{{ $link['url'] }}" class="hover:underline">{{ $link['label'] }}</a></li>
        @endforeach
    </ul>
</nav>
