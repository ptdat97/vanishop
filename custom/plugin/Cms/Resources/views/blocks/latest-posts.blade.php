@if ($posts !== [])
    <section class="my-10">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-2xl font-semibold">{{ $title !== '' ? $title : 'Bài viết mới' }}</h2>
            <a href="{{ route('storefront.p.vani-cms.blog') }}" class="text-sm underline">Xem tất cả</a>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                @include('vani-cms::_post-card', ['post' => $post])
            @endforeach
        </div>
    </section>
@endif
