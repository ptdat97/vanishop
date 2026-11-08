<section class="mb-10" data-block="product_grid" data-vani-motion="rise">
    @if ($title !== '')<h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>@endif
    <div class="grid grid-cols-2 gap-6 md:grid-cols-4" data-vani-motion-stagger>
        @foreach ($products as $product)
            @include('theme::partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
