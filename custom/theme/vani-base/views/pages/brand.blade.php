@extends('theme::layouts.app')

@section('title', $brand['meta_title'] ?? $brand['name'])
@section('description', $brand['meta_description'] ?? '')

@section('content')
    @include('theme::partials.breadcrumb', ['crumbs' => [['label' => __('Thương hiệu'), 'url' => vani_route('storefront.brands')], ['label' => $brand['name']]]])
    <header class="mb-6 flex items-center gap-4">
        @if ($brand['logo_url'])
            <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }}" class="h-14" data-vani-motion-hover>
        @endif
        <div>
            <h1 class="text-2xl font-semibold">{{ $brand['name'] }}</h1>
            @if ($brand['description'])
                <p class="text-slate-600">{{ $brand['description'] }}</p>
            @endif
        </div>
    </header>
    @include('theme::partials.listing', ['listingContext' => ['type' => 'brand', 'category' => null, 'brand' => $brand]])
@endsection
