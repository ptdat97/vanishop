@extends('theme::layouts.app')

@section('title', 'Hello World')

@section('content')
    <h1 class="text-2xl font-semibold">Hello World</h1>
    <p class="mt-2 text-slate-600">{{ __('vani-hello-world::storefront.page_intro') }}</p>
@endsection
