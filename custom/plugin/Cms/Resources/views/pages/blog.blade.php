@extends('theme::layouts.app')

@section('title', __('Tin tức'))

@section('content')
    <h1 class="mb-6 text-3xl font-semibold">{{ __('Tin tức') }}</h1>
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
