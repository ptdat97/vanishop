@extends('theme::layouts.app')

@section('title', __('theme::common.cart'))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ __('theme::common.cart') }}</h1>

    {{-- app.js (0.3.94): sửa số lượng, xoá, chọn dòng, mã giảm giá qua window.vani.cart — thay vùng này, không tải lại trang. --}}
    <div data-vani-cart-page aria-live="polite">
        @include('theme::partials.cart-page')
    </div>
@endsection
