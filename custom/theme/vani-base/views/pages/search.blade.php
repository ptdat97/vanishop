@extends('theme::layouts.app')

@section('title', $query === '' ? 'Tất cả sản phẩm' : 'Tìm "'.$query.'"')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold">{{ $query === '' ? 'Tất cả sản phẩm' : 'Kết quả cho "'.$query.'"' }}</h1>
    @include('theme::partials.listing')
@endsection
