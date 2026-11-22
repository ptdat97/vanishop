@extends('theme::layouts.app')

@section('content')
    <x-vani::hook-slot name="vani.storefront.home.before_blocks" />
    @if ($blocks !== null)
        <h1 class="sr-only">{{ config('app.name') }}</h1>
        @foreach ($blocks as $block)
            {{ $block['html'] }}
        @endforeach
    @else
        <section class="mb-12" aria-labelledby="home-new-arrivals">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-x-4 border-b border-border pb-3">
                <h1 id="home-new-arrivals" class="vani-editorial-title">{{ __('theme::catalog.new_arrivals') }}</h1>
                @if ($newest !== [])
                    <a href="{{ vani_route('storefront.search') }}" class="vani-text-link">{{ __('theme::product.browse_all_products') }}</a>
                @endif
            </div>
            @if ($newest !== [])
                @include('theme::partials.product-grid', ['products' => $newest, 'stagger' => true, 'eager' => 2])
            @else
                @include('theme::partials.empty-state', ['emptyMessage' => __('theme::catalog.no_matching_products'), 'emptyClass' => 'bg-subtle p-8 text-center text-accent'])
            @endif
        </section>

        @if ($categories !== [])
            <section class="mb-12" aria-labelledby="home-categories">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-x-4 border-b border-border pb-3">
                    <h2 id="home-categories" class="text-2xl font-medium tracking-tight">{{ __('theme::common.categories') }}</h2>
                    <a href="{{ vani_route('storefront.categories') }}" class="vani-text-link">{{ __('theme::catalog.browse_categories') }}</a>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach (array_slice($categories, 0, 8) as $category)
                        @include('theme::partials.category-card', ['category' => $category])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($brands !== [])
            <section class="mb-10">
                <h2 class="mb-5 text-2xl font-medium tracking-tight">{{ __('theme::common.brands') }}</h2>
                @include('theme::partials.brand-grid', ['brands' => $brands])
            </section>
        @endif
    @endif
    <x-vani::hook-slot name="vani.storefront.home.after_blocks" />
@endsection
