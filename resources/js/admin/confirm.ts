import { reactive } from 'vue';

export interface ConfirmField {
    key: string;
    label: string;
    required?: boolean;
    maxlength?: number;
    placeholder?: string;
    /** Giá trị ban đầu (vd. tên hiện tại khi đổi tên). */
    value?: string;
}

export interface ConfirmOptions {
    title: string;
    message?: string;
    confirmLabel?: string;
    tone?: 'primary' | 'danger';
    /** Ô nhập lý do trong hộp xác nhận (thay `prompt()`); kết quả trả về là nội dung đã nhập. */
    reason?: Omit<ConfirmField, 'key'>;
}

export interface PromptOptions extends Omit<ConfirmOptions, 'reason'> {
    fields: ConfirmField[];
}

interface ConfirmState {
    open: boolean;
    options: PromptOptions | null;
    resolve: ((result: Record<string, string> | false) => void) | null;
}

/** Trạng thái hộp xác nhận dùng chung; `ConfirmDialog` trong AdminLayout hiển thị nó. */
export const confirmState = reactive<ConfirmState>({ open: false, options: null, resolve: null });

/** Hỏi một hoặc nhiều giá trị (thay chuỗi `prompt()`); `false` khi huỷ, ngược lại là giá trị đã nhập theo `key`. */
export function promptFields(options: PromptOptions): Promise<Record<string, string> | false> {
    confirmState.resolve?.(false);

    return new Promise((resolve) => {
        confirmState.options = options;
        confirmState.resolve = resolve;
        confirmState.open = true;
    });
}

/**
 * Hỏi xác nhận (thay `confirm()`/`prompt()` của trình duyệt). Trả `false` khi huỷ; khi đồng ý trả lý do đã nhập
 * (chuỗi rỗng nếu không hỏi lý do).
 */
export async function confirmAction(options: ConfirmOptions): Promise<string | false> {
    const { reason, ...rest } = options;
    const result = await promptFields({ ...rest, fields: reason ? [{ key: 'reason', ...reason }] : [] });

    return result === false ? false : (result.reason ?? '');
}

export function settleConfirm(result: Record<string, string> | false): void {
    const resolve = confirmState.resolve;
    confirmState.open = false;
    confirmState.resolve = null;
    resolve?.(result);
}
