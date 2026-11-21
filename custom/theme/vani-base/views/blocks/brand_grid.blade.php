<section class="mb-10" data-block="brand_grid" data-vani-motion="rise">
    @if ($title !== '')<h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>@endif
    @include('theme::partials.brand-grid', ['brands' => $brands])
</section>
