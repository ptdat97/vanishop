@extends('theme::layouts.app')

@section('title', __('vani-bundles::texts.page_title'))

@section('content')
    <h1 class="mb-6 text-3xl font-medium tracking-tight">{{ __('vani-bundles::texts.page_title') }}</h1>

    <x-vani::hook-slot name="vani.bundles.page.before_list" />

    @if ($bundles === [])
        <p class="border border-border p-5 text-sm text-accent">{{ __('vani-bundles::texts.page_empty') }}</p>
    @else
        <div class="grid items-start gap-4 lg:grid-cols-2" data-vani-bundle-list>
            @foreach ($bundles as $bundle)
                @include('vani-bundles::bundle', ['bundle' => $bundle, 'scriptSrc' => $scriptSrc, 'pageHeading' => true])
            @endforeach
        </div>
    @endif

    @if ($paginator->hasPages())
        <nav aria-label="{{ __('theme::common.pagination') }}" class="mt-6 flex justify-between gap-4 text-sm">
            @if ($paginator->previousPageUrl())
                <a class="vani-text-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('vani-bundles::texts.previous_page') }}</a>
            @endif
            @if ($paginator->nextPageUrl())
                <a class="vani-text-link ms-auto" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('vani-bundles::texts.next_page') }}</a>
            @endif
        </nav>
    @endif

    <x-vani::hook-slot name="vani.bundles.page.after_list" />
@endsection
