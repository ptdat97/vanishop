{{-- Bảng size trên trang sản phẩm (vani.size-chart). $chart: SizeCharts::present --}}
<div data-vani-size-chart>
    <button type="button" class="text-sm underline" data-size-chart-open>{{ __('Bảng size') }}</button>
    <dialog class="w-full max-w-2xl rounded-[var(--radius-theme)] p-0 backdrop:bg-black/40" data-size-chart-dialog aria-label="{{ $chart['name'] }}">
        <div class="max-h-[85vh] space-y-4 overflow-y-auto p-5 text-sm">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold">{{ $chart['name'] }}</h2>
                <button type="button" class="text-slate-500" data-size-chart-close aria-label="{{ __('Đóng') }}">✕</button>
            </div>
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
                        <label class="flex flex-col">{{ __('Chiều cao (cm)') }}<input type="number" min="50" max="250" class="w-28 rounded border border-slate-300 px-2 py-1" data-advisor-height></label>
                        <label class="flex flex-col">{{ __('Cân nặng (kg)') }}<input type="number" min="10" max="250" class="w-28 rounded border border-slate-300 px-2 py-1" data-advisor-weight></label>
                        <button type="button" class="rounded bg-primary px-3 py-1.5 text-white" data-advisor-run>{{ __('Gợi ý') }}</button>
                    </div>
                    <p class="mt-2" role="status" data-advisor-result></p>
                    <button type="button" class="mt-1 text-sm underline" data-advisor-pick hidden>{{ __('Chọn size này') }}</button>
                </section>
            @endif
        </div>
    </dialog>
</div>
<script>
(function () {
    var root = document.currentScript.previousElementSibling;
    var dialog = root.querySelector('[data-size-chart-dialog]');
    root.querySelector('[data-size-chart-open]').addEventListener('click', function () { dialog.showModal(); });
    root.querySelector('[data-size-chart-close]').addEventListener('click', function () { dialog.close(); });
    root.querySelectorAll('[data-size-unit]').forEach(function (button) {
        button.addEventListener('click', function () {
            var unit = button.getAttribute('data-size-unit');
            root.querySelectorAll('[data-size-table]').forEach(function (table) { table.hidden = table.getAttribute('data-size-table') !== unit; });
            root.querySelectorAll('[data-size-unit]').forEach(function (other) { other.classList.toggle('font-semibold', other === button); other.classList.toggle('border-primary', other === button); });
        });
    });
    var advisor = root.querySelector('[data-size-advisor]');
    if (!advisor) return;
    var rules = JSON.parse(advisor.getAttribute('data-rules'));
    var result = advisor.querySelector('[data-advisor-result]');
    var pick = advisor.querySelector('[data-advisor-pick]');
    var suggested = null;
    advisor.querySelector('[data-advisor-run]').addEventListener('click', function () {
        var height = parseFloat(advisor.querySelector('[data-advisor-height]').value);
        var weight = parseFloat(advisor.querySelector('[data-advisor-weight]').value);
        var match = rules.find(function (rule) { return height >= rule.height_min && height <= rule.height_max && weight >= rule.weight_min && weight <= rule.weight_max; });
        suggested = match ? match.size : null;
        result.textContent = match ? @json(__('Size gợi ý:')) + ' ' + match.size + (match.note ? ' — ' + match.note : '') : @json(__('Chưa tìm thấy size phù hợp'));
        var radio = suggested && Array.prototype.find.call(document.querySelectorAll('[data-variant-form] input[name="variant_id"]:not(:disabled)'), function (input) {
            return input.closest('label').textContent.trim().split(/\s+/)[0] === suggested && input.closest('[data-color-group]').offsetParent !== null;
        });
        pick.hidden = !radio;
        pick.onclick = function () { radio.checked = true; radio.dispatchEvent(new Event('change', { bubbles: true })); dialog.close(); };
    });
})();
</script>
