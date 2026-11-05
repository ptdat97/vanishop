{{-- Thanh thông báo nhiều dòng (store-content §3.1). Không JS: chỉ hiện dòng đầu. JS (announcement.js) xoay vòng, nút trước/sau. --}}
@php($style = trim(($announcement['bg'] ? "background-color:{$announcement['bg']};" : '').($announcement['fg'] ? "color:{$announcement['fg']};" : '')))
<div class="relative bg-primary px-10 py-2 text-center text-sm text-white [&_a]:underline" data-vani-cms-announcement
     data-pause="{{ $announcement['pause_ms'] }}" data-fade="{{ $announcement['fade_ms'] }}" @if ($announcement['until']) data-until="{{ $announcement['until'] }}" @endif
     @if ($style !== '') style="{{ $style }}" @endif role="region" aria-label="{{ __('Thông báo') }}">
    @foreach ($announcement['lines'] as $index => $line)
        <p data-line @if ($index > 0) hidden @endif>{!! $line !!}</p>
    @endforeach
    @if (count($announcement['lines']) > 1)
        <button type="button" data-prev hidden class="absolute inset-y-0 left-2 px-2" aria-label="{{ __('Thông báo trước') }}">‹</button>
        <button type="button" data-next hidden class="absolute inset-y-0 right-2 px-2" aria-label="{{ __('Thông báo sau') }}">›</button>
        @php($src = vani_asset('vani.cms', 'announcement.js'))
        @if ($src !== '')
            <script src="{{ $src }}" defer></script>
        @endif
    @endif
</div>
