@extends('theme::layouts.app')

@section('title', $query === '' ? __('theme::catalog.all_products') : __('theme::catalog.search_query', ['query' => $query]))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ $query === '' ? __('theme::catalog.all_products') : __('theme::catalog.results_query', ['query' => $query]) }}</h1>
    @if ($query === '' && ($popularSearches ?? []) !== [])
        <nav aria-label="{{ __('theme::catalog.popular_searches') }}" class="mb-6 flex flex-wrap items-center gap-2 text-sm" data-vani-popular-searches>
            <span class="text-slate-500">{{ __('theme::catalog.popular_searches') }}:</span>
            @foreach ($popularSearches as $term)
                <a href="{{ vani_route('storefront.search', ['q' => $term]) }}" class="rounded-full border border-slate-300 px-3 py-1">{{ $term }}</a>
            @endforeach
        </nav>
    @endif
    @include('theme::partials.listing')
@endsection
