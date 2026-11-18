{{-- $src: URL file JS của plugin (rỗng khi chưa build) — provider dựng sẵn. --}}
@if ($src !== '')
    <script src="{{ $src }}" defer data-extras-script></script>
@endif
