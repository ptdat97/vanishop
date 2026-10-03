<article class="overflow-hidden rounded-[var(--radius-theme)] border border-slate-200">
    @if ($post['cover_url'])
        <a href="{{ $post['url'] }}"><img src="{{ $post['cover_url'] }}" alt="" loading="lazy" class="aspect-[16/9] w-full object-cover"></a>
    @endif
    <div class="p-4">
        <h3 class="font-semibold"><a href="{{ $post['url'] }}" class="hover:underline">{{ $post['title'] }}</a></h3>
        @if ($post['published_date'])<p class="mt-1 text-xs text-slate-500">{{ $post['published_date'] }}</p>@endif
        <p class="mt-2 text-sm text-slate-600">{{ $post['excerpt'] }}</p>
    </div>
</article>
