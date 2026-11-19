@extends('theme::layouts.app')

@section('title', __('vani-lookbook::storefront.lookbook'))

@section('content')
    <h1 class="mb-6 text-3xl font-semibold">{{ __('vani-lookbook::storefront.lookbook') }}</h1>
    @if ($lookbooks === [])
        <p class="text-sm text-slate-500">{{ __('vani-lookbook::storefront.no_lookbooks_yet') }}</p>
    @else
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3">
            @foreach ($lookbooks as $lookbook)
                @include('vani-lookbook::_card', ['lookbook' => $lookbook])
            @endforeach
        </div>
        @if ($paginator->hasPages())
            <nav aria-label="{{ __('vani-lookbook::storefront.pagination') }}" class="mt-8 flex justify-between text-sm">
                @if ($paginator->previousPageUrl())<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="underline">{{ __('vani-lookbook::storefront.previous') }}</a>@else<span></span>@endif
                @if ($paginator->nextPageUrl())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="underline">{{ __('vani-lookbook::storefront.next') }}</a>@endif
            </nav>
        @endif
    @endif
@endsection
