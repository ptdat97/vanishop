<section class="mb-10" data-block="extras_products">
    @if ($title !== '')<h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>@endif
    @include('theme::partials.product-grid', ['products' => $products])
</section>
