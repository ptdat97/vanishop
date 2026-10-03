@extends('theme::layouts.app')

@section('title', $page['meta_title'])
@section('description', $page['meta_description'])
@section('canonical', $page['url'])

@if ($preview)
    @push('head')
        <meta name="robots" content="noindex">
    @endpush
@endif

@section('content')
    <article class="mx-auto max-w-3xl">
        @include('vani-cms::_preview-notice')
        <h1 class="mb-6 text-3xl font-semibold">{{ $page['title'] }}</h1>
        {{-- HTML do Markdown render với html_input=strip (không có HTML thô từ người soạn). --}}
        <div class="vani-prose">{!! $page['html'] !!}</div>
    </article>
@endsection
