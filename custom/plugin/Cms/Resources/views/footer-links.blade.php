<nav aria-label="Thông tin" class="text-sm">
    <h2 class="mb-2 font-semibold">Thông tin</h2>
    <ul class="space-y-1">
        @foreach ($links as $link)
            <li><a href="{{ $link['url'] }}" class="hover:underline">{{ $link['label'] }}</a></li>
        @endforeach
    </ul>
</nav>
