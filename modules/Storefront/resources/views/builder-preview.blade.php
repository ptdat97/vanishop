<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('storefront::admin.builder.responsive_preview') }}</title>
    <style>:root{@foreach ($themeTokens ?? [] as $token => $value)--vani-{!! $token !!}:{!! $value !!};@endforeach}</style>
    @vite('custom/theme/vani-base/css/app.css')
</head>
<body class="bg-surface font-sans">
    <main class="container mx-auto space-y-6 px-4 py-6">
        @forelse ($blocks as $block)
            <div inert>{{ $block['html'] }}</div>
        @empty
            <p class="text-sm text-accent">{{ __('storefront::admin.builder.preview_empty') }}</p>
        @endforelse
    </main>
</body>
</html>
