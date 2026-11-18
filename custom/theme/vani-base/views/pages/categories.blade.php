@extends('theme::layouts.app')

@section('title', __('Danh mục sản phẩm'))
@section('description', __('Khám phá danh mục và tìm sản phẩm phù hợp với bạn.'))

@section('content')
    <section class="relative isolate mb-12 flex min-h-80 items-end overflow-hidden rounded-[var(--radius-theme)] bg-gradient-to-br from-primary via-slate-800 to-slate-950 px-6 py-10 text-white sm:min-h-96 sm:px-10 md:px-14 md:py-14" aria-labelledby="categories-title" data-category-banner>
        @if ($bannerImage)
            <img src="{{ $bannerImage }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover" fetchpriority="high" decoding="async"
                @if ($bannerImageSrcset) srcset="{{ $bannerImageSrcset }}" sizes="100vw" @endif>
        @endif
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-slate-950/90 via-slate-900/65 to-slate-900/10"></div>
        <div class="max-w-2xl">
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-white/75">{{ __('Khám phá danh mục') }}</p>
            <h1 id="categories-title" class="text-3xl font-semibold leading-tight sm:text-5xl">{{ __('Tìm sản phẩm phù hợp với bạn') }}</h1>
            <p class="mt-4 max-w-xl text-base text-white/85 sm:text-lg">{{ __('Chọn danh mục để khám phá những sản phẩm phù hợp.') }}</p>
            <a href="#category-list" class="mt-7 inline-flex rounded-[var(--radius-theme)] bg-white px-5 py-3 font-semibold text-slate-900 transition-colors hover:bg-white/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">{{ __('Xem danh mục') }}</a>
        </div>
    </section>

    <section id="category-list" class="mb-14 scroll-mt-24" aria-labelledby="category-list-title">
        <div class="mb-5 flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-primary">{{ __('Danh mục') }}</p>
                <h2 id="category-list-title" class="mt-1 text-2xl font-semibold">{{ __('Danh mục sản phẩm') }}</h2>
            </div>
        </div>

        @if ($categories === [])
            <p class="rounded-[var(--radius-theme)] bg-slate-50 px-5 py-10 text-center text-slate-500">{{ __('Cửa hàng chưa có danh mục nào.') }}</p>
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
                <p class="text-sm font-medium uppercase tracking-wide text-primary">{{ __('Dành cho bạn') }}</p>
                <h2 id="suggested-products-title" class="mt-1 text-2xl font-semibold">{{ __('Sản phẩm gợi ý') }}</h2>
            </div>
            @include('theme::partials.product-grid', ['products' => $suggestedProducts, 'stagger' => true])
        </section>
    @endif
@endsection
