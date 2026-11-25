@extends('theme::layouts.app')

@section('title', __('theme::account.unsubscribe_title'))

@section('content')
    {{-- Link huỷ nhận tin trong email marketing: GET hỏi lại, POST (giữ chữ ký trong URL) mới huỷ. --}}
    <section class="mx-auto max-w-md py-10 text-center" data-unsubscribe>
        <h1 class="mb-3 text-2xl font-semibold">{{ __('theme::account.unsubscribe_title') }}</h1>
        @if ($done)
            <p class="text-sm text-slate-600" role="status">{{ __('theme::account.unsubscribe_done') }}</p>
        @else
            <p class="mb-6 text-sm text-slate-600">{{ __('theme::account.unsubscribe_intro') }}</p>
            <form action="{{ $action }}" method="post">
                @csrf
                <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::account.unsubscribe_confirm') }}</button>
            </form>
        @endif
        <p class="mt-6 text-sm"><a href="{{ vani_route('storefront.home') }}" class="underline">{{ __('theme::account.back_to_store') }}</a></p>
    </section>
@endsection
