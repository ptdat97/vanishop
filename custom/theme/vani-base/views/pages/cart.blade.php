@extends('theme::layouts.app')

@section('title', __('Giỏ hàng'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Giỏ hàng') }}</h1>

    {{-- app.js (0.3.94): sửa số lượng, xoá, chọn dòng, mã giảm giá qua window.vani.cart — thay vùng này, không tải lại trang. --}}
    <div data-vani-cart-page aria-live="polite">
        @include('theme::partials.cart-page')
    </div>
@endsection
