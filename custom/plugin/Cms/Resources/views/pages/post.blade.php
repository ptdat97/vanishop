@extends('theme::layouts.app')

@section('title', $post['meta_title'])
@section('description', $post['meta_description'])
@section('canonical', $post['url'])

@push('head')
    @if ($preview)
        <meta name="robots" content="noindex">
    @endif
    @if ($post['cover_url'])
        <meta property="og:image" content="{{ $post['cover_url'] }}">
    @endif
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $post['title'] }}">
@endpush

@section('content')
    <article class="mx-auto max-w-3xl">
        @include('vani-cms::_preview-notice')
        <nav class="mb-4 text-sm text-slate-500"><a href="{{ route('storefront.p.vani-cms.blog') }}" class="underline">Tin tức</a></nav>
        <h1 class="mb-2 text-3xl font-semibold">{{ $post['title'] }}</h1>
        @if ($post['published_date'])
            <p class="mb-6 text-sm text-slate-500"><time datetime="{{ $post['published_at'] }}">{{ $post['published_date'] }}</time></p>
        @endif
        @if ($post['cover_url'])
            <img src="{{ $post['cover_url'] }}" alt="" class="mb-6 w-full rounded-[var(--radius-theme)]">
        @endif
        <div class="vani-prose">{!! $post['html'] !!}</div>
    </article>
@endsection
