@extends('theme::layouts.app')

@section('title', $post['meta_title'])
@section('description', $post['meta_description'])
{{-- Thẻ chia sẻ do layout của theme in (og:title = tiêu đề trang, og:url = canonical). --}}
@section('og_type', 'article')
@if ($post['cover_url'])
    @section('og_image', $post['cover_url'])
@endif

@push('head')
    @if ($preview)
        <meta name="robots" content="noindex">
    @endif
@endpush

@section('content')
    <article class="mx-auto max-w-3xl">
        @include('vani-cms::_preview-notice')
        <nav class="mb-4 text-sm text-slate-500"><a href="{{ vani_route('storefront.p.vani-cms.blog') }}" class="underline">{{ __('vani-cms::storefront.news') }}</a></nav>
        <h1 class="mb-2 text-3xl font-semibold">{{ $post['title'] }}</h1>
        @if ($post['published_date'])
            <p class="mb-6 text-sm text-slate-500">
                <time datetime="{{ $post['published_at'] }}">{{ $post['published_date'] }}</time>
                @if ($post['category'] ?? null) · <a href="{{ $post['category']['url'] }}" class="underline" data-cms-post-category>{{ $post['category']['name'] }}</a>@endif
            </p>
        @endif
        @if ($post['cover_url'])
            <img src="{{ $post['cover_url'] }}" alt="" class="mb-6 w-full rounded-[var(--radius-theme)]">
        @endif
        <div class="vani-prose">{!! $post['html'] !!}</div>
    </article>
    {{-- Sản phẩm gắn với bài (1.4.0): thẻ sản phẩm của theme. --}}
    @if (($post['products'] ?? []) !== [])
        <section class="mx-auto mt-12 max-w-5xl" data-cms-post-products>
            <h2 class="mb-4 text-lg font-semibold">{{ __('vani-cms::storefront.post_products') }}</h2>
            @include('theme::partials.product-grid', ['products' => $post['products']])
        </section>
    @endif
@endsection
