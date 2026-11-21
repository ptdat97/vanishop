<section class="mb-10" data-block="brand_grid" data-vani-motion="rise">
    @if ($title !== '')<h2 class="mb-5 text-2xl font-medium tracking-tight md:text-3xl">{{ $title }}</h2>@endif
    @include('theme::partials.brand-grid', ['brands' => $brands])
</section>
