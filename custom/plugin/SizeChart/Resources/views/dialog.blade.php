{{--
    Bảng size trên trang sản phẩm (vani.size-chart). $chart: SizeCharts::present
    Hiển thị trong slot `vani.storefront.pdp.add_to_cart_fields` (ADR-025).
    Sử dụng `theme::partials.modal` cho khung dialog dùng chung.
--}}

<div data-vani-size-chart>
    <button type="button" class="text-sm underline" data-size-chart-open>{{ __('Bảng size') }}</button>
    @include('theme::partials.modal', [
        'id' => 'vani-size-chart-dialog',
        'title' => $chart['name'],
        'contentView' => 'vani-size-chart::dialog-content',
        'contentData' => ['chart' => $chart],
        'dialogClass' => 'max-w-2xl',
        'dataAttributes' => ['data-size-chart-dialog' => true],
    ])
</div>
<script>
(function () {
    var root = document.currentScript.previousElementSibling;
    var dialog = root.querySelector('[data-size-chart-dialog]');
    root.querySelector('[data-size-chart-open]').addEventListener('click', function () { dialog.showModal(); });
    root.querySelector('[data-vani-modal-close]').addEventListener('click', function () { dialog.close(); });
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
            return input.closest('label').textContent.trim().split(/\s+/)[0] === suggested && (input.closest('[data-option-group], [data-primary-option]') || input).offsetParent !== null;
        });
        pick.hidden = !radio;
        pick.onclick = function () { radio.checked = true; radio.dispatchEvent(new Event('change', { bubbles: true })); dialog.close(); };
    });
})();
</script>
