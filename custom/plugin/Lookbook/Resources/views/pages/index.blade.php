@extends('theme::layouts.app')

@section('title', __('Lookbook'))

@section('content')
    <h1 class="mb-6 text-3xl font-semibold">{{ __('Lookbook') }}</h1>
    @if ($lookbooks === [])
        <p class="text-sm text-slate-500">{{ __('Chưa có lookbook.') }}</p>
    @else
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3">
            @foreach ($lookbooks as $lookbook)
                @include('vani-lookbook::_card', ['lookbook' => $lookbook])
            @endforeach
        </div>
        @if ($paginator->hasPages())
            <nav aria-label="{{ __('Phân trang') }}" class="mt-8 flex justify-between text-sm">
                @if ($paginator->previousPageUrl())<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="underline">{{ __('← Trước') }}</a>@else<span></span>@endif
                @if ($paginator->nextPageUrl())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="underline">{{ __('Sau →') }}</a>@endif
            </nav>
        @endif
    @endif
@endsection
