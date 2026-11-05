@php($src = vani_asset('vani.storefront-extras', 'extras.js'))
@if ($src !== '')
    <script src="{{ $src }}" defer data-extras-script></script>
@endif
