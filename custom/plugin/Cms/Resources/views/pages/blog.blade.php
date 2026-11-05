@extends('theme::layouts.app')

@section('title', $category['meta_title'] ?? __('Tin tức'))
@if ($category['meta_description'] ?? null)
    @section('description', $category['meta_description'])
@endif

@section('content')
    <h1 class="mb-2 text-3xl font-semibold">{{ $category['name'] ?? __('Tin tức') }}</h1>
    @if ($category['description'] ?? null)
        <p class="mb-4 text-slate-600">{{ $category['description'] }}</p>
    @endif
    {{-- Chuyên mục (1.4.0): chỉ chuyên mục có bài công khai. --}}
    @if (($categories ?? []) !== [])
        <nav aria-label="{{ __('Chuyên mục') }}" class="mb-6 flex flex-wrap gap-2 text-sm" data-cms-categories>
            <a href="{{ vani_route('storefront.p.vani-cms.blog') }}" @class(['rounded-full border px-3 py-1', 'border-primary font-semibold text-primary' => $category === null, 'border-slate-300' => $category !== null])>{{ __('Tất cả') }}</a>
            @foreach ($categories as $item)
                <a href="{{ $item['url'] }}" @class(['rounded-full border px-3 py-1', 'border-primary font-semibold text-primary' => ($category['slug'] ?? null) === $item['slug'], 'border-slate-300' => ($category['slug'] ?? null) !== $item['slug']])>{{ $item['name'] }}</a>
            @endforeach
        </nav>
    @endif
    @if ($posts === [])
        <p class="text-sm text-slate-500">{{ __('Chưa có bài viết.') }}</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                @include('vani-cms::_post-card', ['post' => $post])
            @endforeach
        </div>
        @if ($paginator->hasPages())
            <nav aria-label="{{ __('Phân trang') }}" class="mt-8 flex justify-between text-sm">
                @if ($paginator->previousPageUrl())<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="underline">{{ __('← Mới hơn') }}</a>@else<span></span>@endif
                @if ($paginator->nextPageUrl())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="underline">{{ __('Cũ hơn →') }}</a>@endif
            </nav>
        @endif
    @endif
@endsection
