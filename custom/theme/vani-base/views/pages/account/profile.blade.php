@extends('theme::layouts.app')

@section('title', 'Hồ sơ')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <div class="grid gap-8 md:grid-cols-[12rem_1fr]">
        @include('theme::partials.account-menu')
        <section class="max-w-lg">
            <h1 class="mb-6 text-2xl font-semibold">Hồ sơ</h1>
            <form action="{{ route('storefront.account.profile.update') }}" method="post" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm">Số điện thoại</label>
                    <p class="mt-1 text-slate-600">{{ $customer->phone }}</p>
                </div>
                <div>
                    <label for="full_name" class="block text-sm">Họ tên</label>
                    <input id="full_name" name="full_name" type="text" autocomplete="name" required value="{{ old('full_name', $customer->fullName) }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('full_name')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $customer->email) }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('email')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="birth_date" class="block text-sm">Ngày sinh</label>
                        <input id="birth_date" name="birth_date" type="date" autocomplete="bday" value="{{ old('birth_date', $customer->birthDate) }}" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error('birth_date')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="gender" class="block text-sm">Giới tính</label>
                        <select id="gender" name="gender" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                            <option value="">—</option>
                            @foreach (['female' => 'Nữ', 'male' => 'Nam', 'other' => 'Khác'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('gender', $customer->gender) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('gender')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-2 font-medium text-white">Lưu hồ sơ</button>
            </form>

            <h2 class="mb-1 mt-10 text-lg font-semibold">{{ $customer->hasPassword ? 'Đổi mật khẩu' : 'Đặt mật khẩu' }}</h2>
            <p class="mb-4 text-sm text-slate-500">Bạn vẫn đăng nhập được bằng mã OTP gửi tới số điện thoại.</p>
            <form action="{{ route('storefront.account.password.update') }}" method="post" class="space-y-3">
                @csrf
                @method('PUT')
                @if ($customer->hasPassword)
                    <div>
                        <label for="current_password" class="block text-sm">Mật khẩu hiện tại</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                        @error('current_password')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div>
                    <label for="password" class="block text-sm">Mật khẩu mới (tối thiểu 8 ký tự)</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @error('password')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm">Nhập lại mật khẩu mới</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                </div>
                <button type="submit" class="rounded-[var(--radius-theme)] border border-primary px-4 py-2">{{ $customer->hasPassword ? 'Đổi mật khẩu' : 'Đặt mật khẩu' }}</button>
            </form>
        </section>
    </div>
@endsection
