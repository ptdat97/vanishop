@extends('theme::layouts.app')

@section('title', __('theme::account.profile'))

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section class="max-w-lg">
            <h1 class="mb-6 text-2xl font-semibold">{{ __('theme::account.profile') }}</h1>
            <form action="{{ vani_route('storefront.account.profile.update') }}" method="post" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm">{{ __('theme::common.phone_number') }}</label>
                    <p class="mt-1 text-slate-600">{{ $customer->phone ?? '—' }}</p>
                </div>
                <div>
                    <label for="full_name" class="block text-sm">{{ __('theme::common.full_name') }}</label>
                    <input id="full_name" name="full_name" type="text" autocomplete="name" required value="{{ old('full_name', $customer->fullName) }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('full_name')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $customer->email) }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('email')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    @if ($customer->email && ! $customer->emailVerified)
                        {{-- Nút gửi lại thuộc form riêng (không lồng form) — thuộc tính `form`. --}}
                        <p class="mt-1 text-sm text-amber-700" data-email-unverified>
                            {{ __('theme::account.email_not_verified_yet') }}
                            <button type="submit" form="resend-email-verification" class="underline">{{ __('theme::account.resend_verification_link') }}</button>
                        </p>
                    @endif
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="birth_date" class="block text-sm">{{ __('theme::account.date_birth') }}</label>
                        <input id="birth_date" name="birth_date" type="date" autocomplete="bday" value="{{ old('birth_date', $customer->birthDate) }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error('birth_date')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="gender" class="block text-sm">{{ __('theme::account.gender') }}</label>
                        <select id="gender" name="gender" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">—</option>
                            @foreach (['female' => __('theme::account.female'), 'male' => 'Nam', 'other' => __('theme::account.other')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('gender', $customer->gender) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('gender')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('theme::account.save_profile') }}</button>
            </form>
            @if ($customer->email && ! $customer->emailVerified)
                <form id="resend-email-verification" action="{{ vani_route('storefront.account.email.resend') }}" method="post" hidden>@csrf</form>
            @endif

            <h2 class="mb-1 mt-10 text-lg font-semibold">{{ $customer->hasPassword ? __('theme::account.change_password') : __('theme::account.set_password') }}</h2>
            <p class="mb-4 text-sm text-slate-500">{{ __('theme::account.set_password_hint') }}</p>
            <form action="{{ vani_route('storefront.account.password.update') }}" method="post" class="space-y-3">
                @csrf
                @method('PUT')
                @if ($customer->hasPassword)
                    <div>
                        <label for="current_password" class="block text-sm">{{ __('theme::account.current_password') }}</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error('current_password')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div>
                    <label for="password" class="block text-sm">{{ __('theme::account.new_password_at_least_8') }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('password')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm">{{ __('theme::account.confirm_new_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                </div>
                <button type="submit" class="rounded-[var(--radius-theme)] border border-primary px-4 py-2">{{ $customer->hasPassword ? __('theme::account.change_password') : __('theme::account.set_password') }}</button>
            </form>

            <h2 class="mb-1 mt-10 text-lg font-semibold">{{ __('theme::account.marketing_title') }}</h2>
            <form action="{{ vani_route('storefront.account.marketing.update') }}" method="post" class="space-y-3" data-account-marketing>
                @csrf
                @method('PUT')
                <input type="hidden" name="channel" value="email">
                <input type="hidden" name="purpose" value="marketing">
                <label class="flex items-start gap-2 text-sm">
                    <input type="hidden" name="granted" value="0">
                    <input type="checkbox" name="granted" value="1" @checked($marketingEmail ?? false) class="mt-0.5">
                    <span>{{ __('theme::account.marketing_email_label') }}</span>
                </label>
                <button type="submit" class="rounded-[var(--radius-theme)] border border-primary px-4 py-2">{{ __('theme::account.save_preferences') }}</button>
            </form>

            <h2 class="mb-1 mt-10 text-lg font-semibold">{{ __('theme::account.delete_account') }}</h2>
            <p class="text-sm text-slate-500">{{ __('theme::account.deletion_intro') }} <a href="{{ vani_route('storefront.account.delete') }}" class="text-red-700 underline">{{ __('theme::account.delete_account') }}</a></p>
            <x-vani::hook-slot name="vani.storefront.account.profile" :args="[$customer]" />
        </section>
    </div>
@endsection
