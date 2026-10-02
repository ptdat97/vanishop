<section class="mb-10 max-w-3xl" data-block="rich_text">
    @if ($title !== '')<h2 class="mb-3 text-lg font-semibold">{{ $title }}</h2>@endif
    <div class="text-slate-700">{!! nl2br(e($body)) !!}</div>
</section>
