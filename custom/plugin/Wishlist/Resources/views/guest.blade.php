@extends('theme::layouts.app')

@section('title', __('vani-wishlist::storefront.wishlist_products'))

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('vani-wishlist::storefront.wishlist_products') }}</h1>
    <div data-wishlist-guest="{{ vani_route('storefront.p.vani-wishlist.preview') }}" aria-live="polite">
        <p data-guest-empty>{{ __('vani-wishlist::storefront.empty') }}</p>
        <button type="button" hidden data-guest-retry>{{ __('theme::catalog.try_again') }}</button>
        <div data-guest-items></div>
    </div>
    <noscript><a href="{{ vani_route('storefront.p.vani-wishlist.account.index') }}">{{ __('vani-wishlist::storefront.sign_in_to_save') }}</a></noscript>
@endsection
