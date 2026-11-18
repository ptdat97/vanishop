{{--
    Thành phần thông báo dùng chung cho storefront. Tham số: messages ([{type, message, dismissible}], mặc định []),
    fromFlash (đọc flash `status`/`error`, mặc định true), dismissible (mặc định true), class, pageCache.
    $notifications do Core dựng sẵn (NotificationComposer): [{id, type, role, message, dismissible}].
--}}
<div class="vani-notification {{ $class ?? '' }}" data-vani-notification-container>
    @if ($notifications !== [])
        @foreach ($notifications as $item)
            @php($type = $item['type'])
            <div id="{{ $item['id'] }}"
                 class="vani-notification-item mb-3 rounded-[var(--radius-theme)] border p-3 text-sm leading-relaxed
                        @if ($type === 'success') bg-emerald-50 border-emerald-200 text-emerald-800
                        @elseif ($type === 'error') bg-red-50 border-red-200 text-red-700
                        @elseif ($type === 'warning') bg-amber-50 border-amber-200 text-amber-800
                        @else bg-blue-50 border-blue-200 text-blue-800 @endif"
                 role="{{ $item['role'] }}"
                 aria-live="polite"
                 data-vani-notification>
                <div class="flex items-start gap-3">
                    <p>{{ $item['message'] }}</p>
                    @if ($item['dismissible'])
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