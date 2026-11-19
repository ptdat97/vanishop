@extends('theme::layouts.app')

@section('title', $category['meta_title'] ?? __('vani-cms::storefront.news'))
@if ($category['meta_description'] ?? null)
    @section('description', $category['meta_description'])
@endif

@section('content')
    <h1 class="mb-2 text-3xl font-semibold">{{ $category['name'] ?? __('vani-cms::storefront.news') }}</h1>
    @if ($category['description'] ?? null)
        <p class="mb-4 text-slate-600">{{ $category['description'] }}</p>
    @endif
    {{-- Chuyên mục (1.4.0): chỉ chuyên mục có bài công khai. --}}
    @if (($categories ?? []) !== [])
        <nav aria-label="{{ __('vani-cms::storefront.categories') }}" class="mb-6 flex flex-wrap gap-2 text-sm" data-cms-categories>
            <a href="{{ vani_route('storefront.p.vani-cms.blog') }}" @class(['rounded-full border px-3 py-1', 'border-primary font-semibold text-primary' => $category === null, 'border-slate-300' => $category !== null])>{{ __('vani-cms::storefront.all') }}</a>
            @foreach ($categories as $item)
                <a href="{{ $item['url'] }}" @class(['rounded-full border px-3 py-1', 'border-primary font-semibold text-primary' => ($category['slug'] ?? null) === $item['slug'], 'border-slate-300' => ($category['slug'] ?? null) !== $item['slug']])>{{ $item['name'] }}</a>
            @endforeach
        </nav>
    @endif
    @if ($posts === [])
        <p class="text-sm text-slate-500">{{ __('vani-cms::storefront.no_posts_yet') }}</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                @include('vani-cms::_post-card', ['post' => $post])
            @endforeach
        </div>
        @if ($paginator->hasPages())
            <nav aria-label="{{ __('vani-cms::storefront.pagination') }}" class="mt-8 flex justify-between text-sm">
                @if ($paginator->previousPageUrl())<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="underline">{{ __('vani-cms::storefront.newer') }}</a>@else<span></span>@endif
                @if ($paginator->nextPageUrl())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="underline">{{ __('vani-cms::storefront.older') }}</a>@endif
            </nav>
        @endif
    @endif
@endsection
