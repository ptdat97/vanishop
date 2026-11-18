{{-- Thẻ danh mục cho trang tổng hợp: $category gồm slug, name, image_url và parent_name tùy chọn. --}}
<a href="{{ vani_route('storefront.category', $category['slug']) }}" class="group overflow-hidden rounded-[var(--radius-theme)] border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" data-category-card>
    <div class="aspect-[4/3] overflow-hidden bg-slate-100">
        @if ($category['image_url'])
            <img src="{{ $category['image_url'] }}" alt="" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy" decoding="async">
        @else
            <div class="flex h-full items-center justify-center bg-gradient-to-br from-primary/10 to-slate-100 text-primary" aria-hidden="true">
                <svg viewBox="0 0 48 48" class="h-12 w-12" fill="none"><path d="M7 13.5A3.5 3.5 0 0 1 10.5 10h9l4 4h14A3.5 3.5 0 0 1 41 17.5v18a3.5 3.5 0 0 1-3.5 3.5h-27A3.5 3.5 0 0 1 7 35.5v-22Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/></svg>
            </div>
        @endif
    </div>
    <div class="p-3 sm:p-4">
        @if ($category['parent_name'] ?? null)
            <p class="mb-1 text-xs text-slate-500">{{ $category['parent_name'] }}</p>
        @endif
        <h3 class="font-medium group-hover:text-primary">{{ $category['name'] }}</h3>
    </div>
</a>
