@extends('theme::layouts.app')

@section('title', $category['name'])
@section('canonical', route('storefront.category', $category['slug']))

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">{{ $category['name'] }}</h1>
    @if ($category['description'])
        <p class="mb-6 text-slate-600">{{ $category['description'] }}</p>
    @endif
    @include('theme::partials.listing')
@endsection
