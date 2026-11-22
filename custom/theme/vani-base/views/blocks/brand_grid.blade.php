<section class="mb-12 md:mb-16" data-block="brand_grid" data-vani-motion="rise">
    @if ($title !== '')<h2 class="mb-5 border-b border-border pb-3 text-2xl font-medium tracking-tight md:text-3xl">{{ $title }}</h2>@endif
    @include('theme::partials.brand-grid', ['brands' => $brands])
</section>
