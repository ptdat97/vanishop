@extends('theme::layouts.app')

@section('title', __('theme::catalog.shop_by_category'))
@section('description', __('theme::catalog.explore_categories_intro'))

@section('content')
    @include('theme::partials.catalog-banner', [
        'titleId' => 'categories-title',
        'bannerAttribute' => 'data-category-banner',
        'eyebrow' => __('theme::catalog.explore_categories'),
        'heading' => __('theme::catalog.home_intro'),
        'intro' => __('theme::catalog.categories_intro'),
        'actionUrl' => '#category-list',
        'actionLabel' => __('theme::catalog.browse_categories'),
    ])

    <section id="category-list" class="mb-14 scroll-mt-24" aria-labelledby="category-list-title">
        <div class="mb-5 flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-primary">{{ __('theme::common.categories') }}</p>
                <h2 id="category-list-title" class="mt-1 text-2xl font-semibold">{{ __('theme::catalog.shop_by_category') }}</h2>
            </div>
        </div>

        @if ($categories === [])
            <p class="rounded-[var(--radius-theme)] bg-slate-50 px-5 py-10 text-center text-slate-500">{{ __('theme::catalog.no_categories') }}</p>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($categories as $category)
                    @include('theme::partials.category-card', ['category' => $category])
                @endforeach
            </div>
        @endif
    </section>

    @if ($suggestedProducts !== [])
        <section aria-labelledby="suggested-products-title" data-suggested-products>
            <div class="mb-5">
                <p class="text-sm font-medium uppercase tracking-wide text-primary">{{ __('theme::catalog.picked_for_you') }}</p>
                <h2 id="suggested-products-title" class="mt-1 text-2xl font-semibold">{{ __('theme::catalog.recommended_products') }}</h2>
            </div>
            @include('theme::partials.product-grid', ['products' => $suggestedProducts, 'stagger' => true])
        </section>
    @endif
@endsection
