@extends('theme::layouts.app')

@section('title', 'Xoá tài khoản')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section class="max-w-lg">
            <h1 class="mb-4 text-2xl font-semibold">Xoá tài khoản</h1>
            <div class="mb-6 space-y-2 text-sm text-slate-600">
                <p>Khi xoá, cửa hàng gỡ họ tên, số điện thoại, email, ngày sinh, sổ địa chỉ và các đồng ý nhận tin của bạn, đồng thời đăng xuất mọi thiết bị.</p>
                <p>Đơn hàng đã đặt vẫn được lưu (không gắn với tài khoản) theo nghĩa vụ kế toán, thuế. Sau khi xoá, bạn vẫn đăng ký lại được bằng số điện thoại này.</p>
            </div>

            @if ($blockers !== [])
                <div role="alert" class="rounded-[var(--radius-theme)] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-medium">Chưa xoá được tài khoản vì còn việc đang xử lý:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($blockers as $blocker)
                            <li>{{ $blocker['label'] }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2">Vui lòng chờ hoàn tất hoặc <a href="{{ vani_route('storefront.account.orders') }}" class="underline">xem đơn hàng</a>.</p>
                </div>
            @elseif (! $otpSent)
                <form action="{{ vani_route('storefront.account.delete.otp') }}" method="post">
                    @csrf
                    <p class="mb-3 text-sm">Mã xác nhận sẽ được gửi tới {{ $customer->phone }}.</p>
                    <button type="submit" class="rounded-[var(--radius-theme)] border border-red-600 px-4 py-2 font-medium text-red-700">Gửi mã xác nhận</button>
                </form>
            @else
                <form action="{{ vani_route('storefront.account.delete.confirm') }}" method="post" class="space-y-3">
                    @csrf
                    <div>
                        <label for="code" class="block text-sm">Mã xác nhận gửi tới {{ $customer->phone }}</label>
                        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error('code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-[var(--radius-theme)] bg-red-600 px-4 py-2 font-medium text-white">Xoá vĩnh viễn tài khoản</button>
                        <a href="{{ vani_route('storefront.account.profile') }}" class="text-sm underline">Thôi, giữ tài khoản</a>
                    </div>
                </form>
                <form action="{{ vani_route('storefront.account.delete.otp') }}" method="post" class="mt-3">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 underline">Gửi lại mã</button>
                </form>
            @endif
        </section>
    </div>
@endsection
