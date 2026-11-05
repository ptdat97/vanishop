<a href="{{ $lookbook['url'] }}" class="group block" data-lookbook-card>
    <div class="aspect-[4/5] overflow-hidden rounded-[var(--radius-theme)] bg-slate-100">
        @if ($lookbook['cover_url'])
            <img src="{{ $lookbook['cover_url'] }}" alt="" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
        @endif
    </div>
    <p class="mt-2 font-medium group-hover:underline">{{ $lookbook['name'] }}</p>
    <p class="text-sm text-slate-500">{{ trans_choice(':count ảnh', $lookbook['images_count'], ['count' => $lookbook['images_count']]) }}</p>
</a>
