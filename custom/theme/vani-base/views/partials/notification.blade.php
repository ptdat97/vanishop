{{--
    Thành phần thông báo dùng chung cho storefront.
    Hiển thị từ `messages` (nếu truyền vào) hoặc session flash (`status` → thành công, `error` → lỗi).

    Tham số:
    - messages: [{type, message, dismissible}, ...] (mặc định [])
    - fromFlash: đọc từ session flash hay không (mặc định true)
    - dismissible: cho phép đóng từng thông báo (mặc định true)
    - class: class thêm cho container
--}}
@php
    $messages = $messages ?? [];
    $fromFlash = $fromFlash ?? true;
    $dismissible = $dismissible ?? true;
    $class = $class ?? '';
    $pageCache = $pageCache ?? false;

    $items = $messages;
    // Trang cache CDN không render flash vào HTML (giữ HTML giống nhau mọi khách);
    // JS sẽ đọc flash từ /_vani/phien và điền vào chỗ trống bên dưới.
    if ($fromFlash && empty($items) && ! $pageCache) {
        $status = session('status') ?? null;
        $error = session('error') ?? null;
        if ($status) {
            $items[] = ['type' => 'success', 'message' => $status, 'dismissible' => $dismissible];
        }
        if ($error) {
            $items[] = ['type' => 'error', 'message' => $error, 'dismissible' => $dismissible];
        }
    }
@endphp

<div class="vani-notification {{ $class }}" data-vani-notification-container>
    @if (count($items) > 0)
        @foreach ($items as $index => $item)
            @php
                $type = $item['type'] ?? 'info';
                $text = $item['message'] ?? '';
                $canDismiss = $item['dismissible'] ?? $dismissible;
                $id = "vani-notification-{{ $index }}";
            @endphp
            <div id="{{ $id }}"
                 class="vani-notification-item mb-3 rounded-[var(--radius-theme)] border p-3 text-sm leading-relaxed
                        @if ($type === 'success') bg-emerald-50 border-emerald-200 text-emerald-800
                        @elseif ($type === 'error') bg-red-50 border-red-200 text-red-700
                        @elseif ($type === 'warning') bg-amber-50 border-amber-200 text-amber-800
                        @else bg-blue-50 border-blue-200 text-blue-800 @endif"
                 role="{{ $type === 'error' ? 'alert' : 'status' }}"
                 aria-live="polite"
                 data-vani-notification>
                <div class="flex items-start gap-3">
                    <p>{{ $text }}</p>
                    @if ($canDismiss)
                        <button type="button"
                                class="mt-0.5 shrink-0 inline-flex items-center rounded text-current hover:bg-current hover:text-white opacity-70 hover:opacity-100"
                                data-vani-notification-close
                                aria-label="{{ __('Đóng') }}"
                                title="{{ __('Đóng') }}">×</button>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        {{-- Chỗ trống để JS điền khi trang cache CDN (không có session trong HTML). --}}
        <p role="status" class="mb-3 rounded-[var(--radius-theme)] bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800 hidden" data-vani-flash="status"></p>
        <p role="alert" class="mb-3 rounded-[var(--radius-theme)] bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-700 hidden" data-vani-error-for="business"></p>
    @endif
</div>