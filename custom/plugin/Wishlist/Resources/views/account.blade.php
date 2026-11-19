@extends('theme::layouts.app')

@section('title', __('vani-wishlist::storefront.wishlist_products'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[14rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-6 text-2xl font-semibold">{{ __('vani-wishlist::storefront.wishlist_products') }}</h1>
            @if ($items === [])
                <p class="text-slate-500">{{ __('vani-wishlist::storefront.empty') }}</p>
            @else
                <div class="grid grid-cols-2 gap-6 md:grid-cols-3" data-vani-wishlist-page>
                    @foreach ($items as $item)
                        <div>
                            @if ($item['card'] !== null)
                                @include('theme::partials.product-card', ['product' => $item['card'], 'layout' => [896, 3, 2]])
                            @else
                                <p class="rounded-[var(--radius-theme)] bg-slate-100 p-6 text-sm text-slate-500">{{ __('vani-wishlist::storefront.no_longer_available') }}</p>
                            @endif
                            <form action="{{ vani_route('storefront.p.vani-wishlist.toggle') }}" method="post" class="mt-2">
                                @csrf
                                <input type="hidden" name="style_id" value="{{ $item['style_id'] }}">
                                <button type="submit" class="text-sm text-slate-500 underline">{{ __('vani-wishlist::storefront.remove_from_wishlist') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
