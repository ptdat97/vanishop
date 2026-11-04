@extends('theme::layouts.app')

@section('title', $category['name'])

@section('content')
    @include('theme::partials.breadcrumb', ['crumbs' => array_map(fn (array $crumb): array => ['label' => $crumb['name'], 'url' => vani_route('storefront.category', $crumb['slug'])], $category['breadcrumb'] ?? [['name' => $category['name'], 'slug' => $category['slug']]])])
    <h1 class="mb-2 text-2xl font-semibold">{{ $category['name'] }}</h1>
    @if ($category['description'])
        <p class="mb-6 text-slate-600">{{ $category['description'] }}</p>
    @endif
    @include('theme::partials.listing', ['listingContext' => ['type' => 'category', 'category' => $category, 'brand' => null]])
@endsection
