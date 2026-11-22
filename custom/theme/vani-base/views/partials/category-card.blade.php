{{-- $category gồm slug, name, image_url và parent_name tùy chọn. --}}
<a href="{{ vani_route('storefront.category', $category['slug']) }}" class="group block min-w-0 border-b border-border pb-3" data-category-card>
    <div class="mb-3 aspect-[4/3] overflow-hidden rounded-[var(--radius-theme)] bg-subtle">
        @if ($category['image_url'])
            <img src="{{ $category['image_url'] }}" alt="" class="h-full w-full object-cover" width="480" height="360" loading="lazy" decoding="async">
        @else
            <div class="grid h-full place-items-center text-accent" aria-hidden="true">
                <svg viewBox="0 0 48 48" class="size-9" fill="none"><path d="M7 13.5A3.5 3.5 0 0 1 10.5 10h9l4 4h14A3.5 3.5 0 0 1 41 17.5v18a3.5 3.5 0 0 1-3.5 3.5h-27A3.5 3.5 0 0 1 7 35.5v-22Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/></svg>
            </div>
        @endif
    </div>
    <div class="flex min-h-11 items-center justify-between gap-2 text-sm font-medium">
        <div class="min-w-0 break-words">
            @if ($category['parent_name'] ?? null)
                <p class="mb-1 text-xs font-normal text-accent">{{ $category['parent_name'] }}</p>
            @endif
            <h3 class="group-hover:underline">{{ $category['name'] }}</h3>
        </div>
        <span aria-hidden="true">›</span>
    </div>
</a>
