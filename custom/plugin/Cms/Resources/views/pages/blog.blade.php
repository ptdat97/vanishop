@extends('theme::layouts.app')

@section('title', 'Tin tức')
@section('canonical', route('storefront.p.vani-cms.blog'))

@section('content')
    <h1 class="mb-6 text-3xl font-semibold">Tin tức</h1>
    @if ($posts === [])
        <p class="text-sm text-slate-500">Chưa có bài viết.</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                @include('vani-cms::_post-card', ['post' => $post])
            @endforeach
        </div>
        @if ($paginator->hasPages())
            <nav aria-label="Phân trang" class="mt-8 flex justify-between text-sm">
                @if ($paginator->previousPageUrl())<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="underline">← Mới hơn</a>@else<span></span>@endif
                @if ($paginator->nextPageUrl())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="underline">Cũ hơn →</a>@endif
            </nav>
        @endif
    @endif
@endsection
