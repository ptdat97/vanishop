@extends('theme::layouts.app')

@section('title', $query === '' ? __('Tất cả sản phẩm') : __('Tìm ":query"', ['query' => $query]))

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ $query === '' ? __('Tất cả sản phẩm') : __('Kết quả cho ":query"', ['query' => $query]) }}</h1>
    @include('theme::partials.listing')
@endsection
