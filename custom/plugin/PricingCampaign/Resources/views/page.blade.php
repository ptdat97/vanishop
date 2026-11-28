@extends('theme::layouts.app')

@section('title', __('vani-pricing-campaign::texts.storefront.page_title'))

@section('content')
    <h1 class="mb-6 text-3xl font-medium tracking-tight">{{ __('vani-pricing-campaign::texts.storefront.page_title') }}</h1>
    @forelse ($campaigns as $campaign)
        @include('vani-pricing-campaign::campaign', ['campaign' => $campaign])
    @empty
        <p class="border border-border p-5 text-sm text-accent">{{ __('vani-pricing-campaign::texts.storefront.page_empty') }}</p>
    @endforelse
    <script src="{{ $scriptSrc }}" defer data-vani-campaign-script
            data-units="{{ json_encode(['d' => __('vani-pricing-campaign::texts.storefront.day'), 'h' => __('vani-pricing-campaign::texts.storefront.hour'), 'm' => __('vani-pricing-campaign::texts.storefront.minute'), 's' => __('vani-pricing-campaign::texts.storefront.second')]) }}"></script>
@endsection
