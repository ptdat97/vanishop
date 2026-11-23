@extends('theme::layouts.app')

@section('title', __('theme::common.order_title', ['number' => $order['number']]))

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section>
            <h1 class="mb-2 text-2xl font-semibold">{{ __('theme::common.order_title', ['number' => $order['number']]) }}</h1>
            <p class="mb-6 text-sm text-slate-500">{{ $order['placed_at_label'] }} · {{ $order['status']['label'] ?? $order['order_status'] }}</p>
            <x-vani::hook-slot name="vani.storefront.order.before_summary" :args="[$order]" />
            @include('theme::partials.order-summary')
            <x-vani::hook-slot name="vani.storefront.account.order_detail" :args="[$order]" />
            @include('theme::partials.order-receipt')
            @include('theme::partials.order-returns')

            @if ($order['can_cancel'])
                <form action="{{ vani_route('storefront.account.order.cancel', $order['id']) }}" method="post" class="mt-8 grid max-w-lg gap-3">
                    @csrf
                    <div>
                        <label for="reason_code" class="block text-sm">{{ __('theme::account.cancellation_reason') }}</label>
                        <select id="reason_code" name="reason_code" required @error('reason_code') aria-invalid="true" aria-describedby="reason-code-error" @enderror
                                class="mt-1 min-h-11 w-full rounded border border-slate-300 px-3 text-base">
                            <option value="">{{ __('theme::account.choose_cancellation_reason') }}</option>
                            @foreach ($cancellationReasons as $code => $label)
                                <option value="{{ $code }}" @selected(old('reason_code') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason_code')<p id="reason-code-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="reason" class="block text-sm">{{ __('theme::account.cancellation_note') }}</label>
                        <textarea id="reason" name="reason" rows="2" maxlength="255" @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror
                                  class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-base">{{ old('reason') }}</textarea>
                        @error('reason')<p id="reason-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="min-h-11 justify-self-start rounded border border-red-300 px-4 py-2 text-sm text-red-700">{{ __('theme::account.cancel_order') }}</button>
                </form>
            @endif
        </section>
    </div>
@endsection
