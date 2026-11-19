{{--
    Thành phần toast (thông báo tự động biến mất) dùng chung cho storefront.
    Hiển thị từ mảng `toasts` truyền vào; mỗi toast có type (success/error/warning/info),
    message, duration (ms, mặc định 5000), dismissible.

    Tham số:
    - toasts: [{type, message, duration, dismissible}, ...] (mặc định [])
    - position: top-right | top-center | bottom-right | bottom-left | top-left (mặc định top-right)
    - class: class thêm cho container
--}}

@php
    $toasts = $toasts ?? [];
    $position = $position ?? 'top-right';
    $class = $class ?? '';

    $positionClass = match ($position) {
        'top-center'    => 'vani-toast-top-center',
        'bottom-right'  => 'vani-toast-bottom-right',
        'bottom-left'   => 'vani-toast-bottom-left',
        'top-left'      => 'vani-toast-top-left',
        default         => 'vani-toast-top-right',
    };
@endphp

<div class="vani-toast-container {{ $positionClass }} {{ $class }}"
     data-vani-toast-container
     aria-label="{{ __('theme::common.notifications') }}"
     aria-atomic="true">
    @foreach ($toasts as $index => $toast)
        @php
            $type = $toast['type'] ?? 'info';
            $text = $toast['message'] ?? '';
            $duration = $toast['duration'] ?? 5000;
            $canDismiss = $toast['dismissible'] ?? true;
            $id = "vani-toast-{$index}";
        @endphp
        <div id="{{ $id }}"
             class="vani-toast mb-2 rounded-[var(--radius-theme)] border p-3 text-sm leading-relaxed shadow-lg
                     @if ($type === 'success') bg-emerald-50 border-emerald-200 text-emerald-800
                     @elseif ($type === 'error') bg-red-50 border-red-200 text-red-700
                     @elseif ($type === 'warning') bg-amber-50 border-amber-200 text-amber-800
                     @else bg-blue-50 border-blue-200 text-blue-800 @endif"
             role="{{ $type === 'error' ? 'alert' : 'status' }}"
             aria-live="polite"
             data-vani-toast
             data-vani-toast-duration="{{ $duration }}"
             data-vani-toast-timer>
            <div class="flex items-start gap-3">
                <p>{{ $text }}</p>
                @if ($canDismiss)
                    <button type="button"
                            class="mt-0.5 shrink-0 inline-flex items-center rounded text-current hover:bg-current hover:text-white opacity-70 hover:opacity-100"
                            data-vani-toast-close
                            aria-label="{{ __('theme::common.close') }}"
                            title="{{ __('theme::common.close') }}">×</button>
                @endif
            </div>
        </div>
    @endforeach
</div>