@extends('theme::layouts.app')

@section('title', __('vani-wishlist::storefront.wishlist_products'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[14rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-6 text-2xl font-semibold">{{ __('vani-wishlist::storefront.wishlist_products') }}</h1>
            @include('vani-wishlist::items')
        </section>
    </div>
@endsection
