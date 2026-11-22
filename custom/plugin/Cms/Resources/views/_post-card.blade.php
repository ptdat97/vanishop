<article class="overflow-hidden rounded-[var(--radius-theme)] border border-slate-200">
    @if ($post['cover_url'])
        <a href="{{ $post['url'] }}"><img src="{{ $post['cover_url'] }}" alt=""
                @if (! empty($post['cover_srcset'])) srcset="{{ $post['cover_srcset'] }}" sizes="(min-width: 72rem) 24rem, (min-width: 48rem) 33vw, 100vw" @endif
                @if (! empty($post['cover_width']) && ! empty($post['cover_height'])) width="{{ $post['cover_width'] }}" height="{{ $post['cover_height'] }}" @endif loading="lazy" class="aspect-[16/9] w-full object-cover"></a>
    @endif
    <div class="p-4">
        <h3 class="font-semibold"><a href="{{ $post['url'] }}" class="hover:underline">{{ $post['title'] }}</a></h3>
        @if ($post['published_date'])<p class="mt-1 text-xs text-slate-500">{{ $post['published_date'] }}</p>@endif
        <p class="mt-2 text-sm text-slate-600">{{ $post['excerpt'] }}</p>
    </div>
</article>
