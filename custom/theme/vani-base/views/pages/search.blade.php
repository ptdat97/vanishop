@extends('theme::layouts.app')

@section('title', $query === '' ? __('Tất cả sản phẩm') : __('Tìm ":query"', ['query' => $query]))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ $query === '' ? __('Tất cả sản phẩm') : __('Kết quả cho ":query"', ['query' => $query]) }}</h1>
    @if ($query === '' && ($popularSearches ?? []) !== [])
        <nav aria-label="{{ __('Tìm kiếm nổi bật') }}" class="mb-6 flex flex-wrap items-center gap-2 text-sm" data-vani-popular-searches>
            <span class="text-slate-500">{{ __('Tìm kiếm nổi bật') }}:</span>
            @foreach ($popularSearches as $term)
                <a href="{{ vani_route('storefront.search', ['q' => $term]) }}" class="rounded-full border border-slate-300 px-3 py-1">{{ $term }}</a>
            @endforeach
        </nav>
    @endif
    @include('theme::partials.listing')
@endsection
