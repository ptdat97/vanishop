{{-- Thanh tiến độ miễn phí giao (vani.shipping-flat-rate). $progress: FreeShippingProgress::forAmount --}}
<div class="my-4 rounded-[var(--radius-theme)] border border-slate-200 p-3 text-sm" data-vani-free-shipping>
    <p @class(['font-medium text-emerald-700' => $progress['achieved']])>{{ $progress['message'] }}</p>
    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress['percent'] }}">
        <div class="h-full rounded-full bg-accent" style="width: {{ $progress['percent'] }}%;@if ($progress['color']) background-color: {{ $progress['color'] }};@endif"></div>
    </div>
</div>
