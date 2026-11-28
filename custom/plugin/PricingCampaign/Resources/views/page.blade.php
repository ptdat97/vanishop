@extends('theme::layouts.app')

@section('title', __('vani-pricing-campaign::texts.storefront.page_title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-3xl font-medium tracking-tight">{{ __('vani-pricing-campaign::texts.storefront.page_title') }}</h1>
        <form method="get" class="flex flex-wrap items-center gap-2 text-sm">
            <div class="flex rounded border border-border overflow-hidden">
                @foreach (['all' => 'filter_all', 'running' => 'filter_running', 'upcoming' => 'filter_upcoming'] as $key => $labelKey)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key === 'all' ? null : $key]) }}"
                       class="px-3 py-1.5 {{ ($status ?? 'all') === $key ? 'bg-slate-900 text-white' : 'hover:bg-slate-100' }}">
                        {{ __('vani-pricing-campaign::texts.storefront.' . $labelKey) }}
                    </a>
                @endforeach
            </div>
            @if (($status ?? 'all') !== 'all')
                <input type="hidden" name="status" value="{{ $status }}" />
            @endif
            <select name="sort" onchange="this.form.submit()" class="rounded border border-border bg-white px-3 py-1.5" aria-label="{{ __('vani-pricing-campaign::texts.storefront.sort_label') }}">
                @foreach (['default' => 'sort_default', 'ending_soon' => 'sort_ending_soon', 'low_stock' => 'sort_low_stock'] as $key => $labelKey)
                    <option value="{{ $key }}" @selected(($sort ?? 'default') === $key)>{{ __('vani-pricing-campaign::texts.storefront.' . $labelKey) }}</option>
                @endforeach
            </select>
        </form>
    </div>
    @forelse ($campaigns as $campaign)
        @include('vani-pricing-campaign::campaign', ['campaign' => $campaign])
    @empty
        <p class="border border-border p-5 text-sm text-accent">{{ __('vani-pricing-campaign::texts.storefront.page_empty') }}</p>
    @endforelse
    <script src="{{ $scriptSrc }}" defer data-vani-campaign-script
            data-units="{{ json_encode(['d' => __('vani-pricing-campaign::texts.storefront.day'), 'h' => __('vani-pricing-campaign::texts.storefront.hour'), 'm' => __('vani-pricing-campaign::texts.storefront.minute'), 's' => __('vani-pricing-campaign::texts.storefront.second')]) }}"></script>
@endsection
