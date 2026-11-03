{{-- Trang bảo trì (503): độc lập với layout — không đọc phiên, giỏ hay menu (route có thể đang chạy không phiên). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('storefront::messages.maintenance_title') }} — {{ config('app.name') }}</title>
    <style>:root{@foreach ($themeTokens ?? [] as $token => $value)--vani-{{ $token }}:{{ $value }};@endforeach}</style>
    @vite(['custom/theme/vani-base/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-surface px-4 font-sans text-slate-900 antialiased">
    <main class="max-w-lg text-center">
        <p class="text-xl font-semibold text-primary">{{ config('app.name') }}</p>
        <h1 class="mt-6 text-2xl font-semibold">{{ __('storefront::messages.maintenance_title') }}</h1>
        <p class="mt-3 whitespace-pre-line text-slate-600">{{ $message }}</p>
    </main>
</body>
</html>
