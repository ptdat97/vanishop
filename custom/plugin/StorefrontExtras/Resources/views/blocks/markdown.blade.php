<section class="mb-10 max-w-3xl" data-block="extras_markdown">
    @if ($title !== '')<h2 class="mb-3 text-lg font-semibold">{{ $title }}</h2>@endif
    {{-- HTML do Markdown sinh, đã bỏ HTML thô và link không an toàn (MarkdownBlock). --}}
    <div class="vani-prose">{!! $html !!}</div>
</section>
