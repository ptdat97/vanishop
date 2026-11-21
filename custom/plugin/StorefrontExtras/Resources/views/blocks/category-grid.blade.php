@if ($categories !== [])
    <section class="mb-10" data-block="extras_category_grid">
        @if ($title !== '')
            <h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>
        @endif
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
            @foreach ($categories as $category)
                @include('theme::partials.category-card', ['category' => $category])
            @endforeach
        </div>
    </section>
@endif
