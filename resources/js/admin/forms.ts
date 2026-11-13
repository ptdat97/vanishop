/**
 * Khoá lạc quan (`lock_version`) của form Admin. Server tăng `lock_version` mỗi lần lưu; form `useForm` giữ giá trị lúc mở trang
 * nên lần lưu thứ hai bị báo "đã được người khác cập nhật" dù chỉ một người sửa. Gắn vào `onSuccess` của lần lưu:
 *   form.put(url, { preserveScroll: true, onSuccess: syncLockVersion(form, () => props.product?.lock_version) })
 * Chỉ nhận phiên bản mới **sau khi chính form này lưu thành công** — tải lại trang vì thao tác khác (tải ảnh…) không đổi phiên
 * bản trong form, nên xung đột thật với người khác vẫn được phát hiện.
 */
export function syncLockVersion(
    form: { lock_version: number | null; defaults: (field: 'lock_version', value: number) => unknown },
    current: () => number | null | undefined,
    then?: () => void,
): () => void {
    return () => {
        const next = current();
        if (typeof next === 'number') {
            form.lock_version = next;
            form.defaults('lock_version', next);
        }
        then?.();
    };
}
