{{-- Slot vani.storefront.account.login: đăng nhập bằng mã OTP gửi tới số điện thoại (plugin vani.phone-otp-login). --}}
<section class="mt-8 border-t border-slate-200 pt-6" data-vani-phone-otp-login>
    <h2 class="mb-3 text-sm font-medium text-slate-600">{{ __('vani-phone-otp-login::storefront.sign_in_with_code_hint') }}</h2>
    <form action="{{ vani_route('storefront.p.vani-phone-otp-login.request') }}" method="post" class="space-y-3">
        @csrf
        <label for="otp-phone" class="block text-sm">{{ __('vani-phone-otp-login::storefront.phone_number') }}</label>
        <input id="otp-phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone', $phone) }}" required class="w-full rounded border border-slate-300 px-3 py-2">
        @error('phone', 'otp')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
        <button type="submit" class="w-full rounded-[var(--radius-theme)] border border-primary px-4 py-2">{{ $phone ? __('vani-phone-otp-login::storefront.resend_code') : __('vani-phone-otp-login::storefront.send_verification_code') }}</button>
    </form>
    @if ($phone)
        <form action="{{ vani_route('storefront.p.vani-phone-otp-login.verify') }}" method="post" class="mt-4 space-y-3">
            @csrf
            <label for="otp-code" class="block text-sm">{{ __('vani-phone-otp-login::storefront.code_sent_to', ['phone' => $phone]) }}</label>
            <input id="otp-code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required class="w-full rounded border border-slate-300 px-3 py-2">
            @error('code', 'otp')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button type="submit" class="w-full rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">{{ __('vani-phone-otp-login::storefront.sign_in_with_code') }}</button>
        </form>
    @endif
</section>
