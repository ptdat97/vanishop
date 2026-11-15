{{--
    Nội dung dialog bảng size (vani.size-chart).
    Được include bởi `theme::partials.modal` qua `dialog.blade.php`.
    $chart: SizeCharts::present
--}}

<div class="max-h-[85vh] space-y-4 overflow-y-auto p-5 text-sm">
    @if ($chart['display_unit'])
        <div class="flex gap-2" role="group" aria-label="{{ __('Đơn vị') }}">
            <button type="button" class="rounded border border-primary px-2 py-1 font-semibold" data-size-unit="base">{{ $chart['unit'] }}</button>
            <button type="button" class="rounded border px-2 py-1" data-size-unit="display">{{ $chart['display_unit'] }}</button>
        </div>
    @endif
    @foreach ($chart['blocks'] as $block)
        @if ($block['type'] === 'image')
            <img src="{{ $block['url'] }}" alt="{{ $block['alt'] }}" loading="lazy" class="w-full rounded">
        @elseif ($block['type'] === 'text')
            <div class="space-y-2 [&_a]:underline [&_ul]:list-disc [&_ul]:pl-5">{!! $block['html'] !!}</div>
        @else
            @foreach (['base' => $block['rows'], 'display' => $block['converted']] as $variant => $rows)
                @continue($rows === null)
                <div class="overflow-x-auto" data-size-table="{{ $variant }}" @if ($variant === 'display') hidden @endif>
                    <table class="w-full border-collapse text-center">
                        @foreach ($rows as $r => $row)
                            <tr @class(['bg-slate-50 font-medium' => $r === 0])>
                                @foreach ($row as $c => $cell)
                                    @if ($r === 0 || $c === 0)
                                        <th class="border border-slate-200 px-2 py-1" scope="{{ $r === 0 ? 'col' : 'row' }}">{{ $cell }}</th>
                                    @else
                                        <td class="border border-slate-200 px-2 py-1">{{ $cell }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        @endif
    @endforeach
    @if ($chart['advisor'])
        <section class="rounded border border-slate-200 p-3" data-size-advisor data-rules='@json($chart['advisor']['rules'])'>
            <h3 class="mb-2 font-semibold">{{ __('Tìm size của tôi') }}</h3>
            <div class="flex flex-wrap items-end gap-3">
                <label class="flex flex-col gap-2">{{ __('Chiều cao (cm)') }}<input type="number" min="50" max="250" class="w-28 rounded border border-slate-300 px-2 py-1" data-advisor-height></label>
                <label class="flex flex-col gap-2">{{ __('Cân nặng (kg)') }}<input type="number" min="10" max="250" class="w-28 rounded border border-slate-300 px-2 py-1" data-advisor-weight></label>
                <button type="button" class="rounded bg-primary px-3 py-1.5 text-white" data-advisor-run>{{ __('Gợi ý') }}</button>
            </div>
            <p class="mt-2 font-semibold" role="status" data-advisor-result></p>
            <button type="button" class="mt-1 text-sm underline" data-advisor-pick hidden>{{ __('Chọn size này') }}</button>
        </section>
    @endif
</div>