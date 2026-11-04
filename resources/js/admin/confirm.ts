import { reactive } from 'vue';

export interface ConfirmOptions {
    title: string;
    message?: string;
    confirmLabel?: string;
    tone?: 'primary' | 'danger';
    /** Ô nhập lý do trong hộp xác nhận (thay `prompt()`); kết quả trả về là nội dung đã nhập. */
    reason?: { label: string; required?: boolean; maxlength?: number; placeholder?: string };
}

interface ConfirmState {
    open: boolean;
    options: ConfirmOptions | null;
    resolve: ((result: string | false) => void) | null;
}

/** Trạng thái hộp xác nhận dùng chung; `ConfirmDialog` trong AdminLayout hiển thị nó. */
export const confirmState = reactive<ConfirmState>({ open: false, options: null, resolve: null });

/**
 * Hỏi xác nhận (thay `confirm()`/`prompt()` của trình duyệt). Trả `false` khi huỷ; khi đồng ý trả lý do đã nhập
 * (chuỗi rỗng nếu không hỏi lý do).
 */
export function confirmAction(options: ConfirmOptions): Promise<string | false> {
    confirmState.resolve?.(false);

    return new Promise((resolve) => {
        confirmState.options = options;
        confirmState.resolve = resolve;
        confirmState.open = true;
    });
}

export function settleConfirm(result: string | false): void {
    const resolve = confirmState.resolve;
    confirmState.open = false;
    confirmState.resolve = null;
    resolve?.(result);
}
