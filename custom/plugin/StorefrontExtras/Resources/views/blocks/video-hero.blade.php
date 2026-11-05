{{-- Video hero: tự phát luôn tắt tiếng; JS tôn trọng "giảm chuyển động" (dừng + hiện nút phát). --}}
<section class="relative mb-10 overflow-hidden rounded-[var(--radius-theme)] bg-slate-900 text-white" data-block="extras_video_hero">
    <video class="h-[60vh] max-h-[640px] w-full object-cover" playsinline muted preload="none" data-extras-video
           @if ($poster !== '') poster="{{ $poster }}" @endif @if ($autoplay) autoplay @endif @if ($loop) loop @endif>
        @if ($videoMobile)<source src="{{ $videoMobile }}" media="(max-width: 767px)" type="video/{{ str_ends_with(strtolower(strtok($videoMobile, '?')), '.webm') ? 'webm' : 'mp4' }}">@endif
        <source src="{{ $video }}" type="video/{{ str_ends_with(strtolower(strtok($video, '?')), '.webm') ? 'webm' : 'mp4' }}">
    </video>
    <button type="button" class="absolute bottom-4 right-4 rounded-full bg-black/60 px-4 py-2 text-sm" data-extras-video-toggle
            data-label-play="{{ __('Phát video') }}" data-label-pause="{{ __('Dừng video') }}">{{ $autoplay ? __('Dừng video') : __('Phát video') }}</button>
    @if ($title !== '' || $text !== '' || ($ctaLabel !== '' && $ctaUrl !== ''))
        <div class="absolute inset-0 flex flex-col items-start justify-end gap-3 bg-gradient-to-t from-black/60 to-transparent p-8">
            @if ($title !== '')<h2 class="text-3xl font-semibold">{{ $title }}</h2>@endif
            @if ($text !== '')<p class="max-w-xl">{{ $text }}</p>@endif
            @if ($ctaLabel !== '' && $ctaUrl !== '')<a href="{{ $ctaUrl }}" class="rounded-[var(--radius-theme)] bg-white px-6 py-3 font-medium text-slate-900">{{ $ctaLabel }}</a>@endif
        </div>
    @endif
</section>
