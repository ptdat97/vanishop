import { reactive, toRaw } from 'vue';

/**
 * Thông báo dạng toast của Admin (một chỗ cho mọi trang và plugin; `Toaster` gắn một lần trong AdminLayout):
 *   toast('Đã lưu.')                       // thành công
 *   toast('Không kết nối được.', 'error')  // lỗi: giữ tới khi đóng
 * Tự hiện: `flash.success` của server và lỗi chung không gắn với ô nhập nào (khoá trong `GENERAL_ERROR_KEYS`, vd.
 * `errors.business`, `errors.product`) — trang không cần tự vẽ khung báo lỗi/thành công.
 */
export type ToastTone = 'success' | 'error' | 'warning' | 'info';

export interface ToastItem {
    id: number;
    message: string;
    tone: ToastTone;
    open: boolean;
}

/**
 * Khoá lỗi chung (không thuộc ô nhập nào) hiện thành toast lỗi. Lỗi theo ô nhập vẫn hiện tại ô (FormField). Thêm khoá ở đây khi
 * server trả lỗi chung với khoá mới; plugin có thể dùng `business`.
 */
export const GENERAL_ERROR_KEYS = [
    'business', 'product', 'lock_version', 'category', 'link_type', 'campaign', 'changes', 'option_type', 'option_value',
];

export const toastState = reactive<{ items: ToastItem[] }>({ items: [] });
let nextId = 0;

export function toast(message: string, tone: ToastTone = 'success'): void {
    if (!message) return;
    // Bỏ toast đã đóng; cùng nội dung đang hiện thì không lặp.
    toastState.items = toastState.items.filter((item) => item.open && !(item.message === message && item.tone === tone));
    toastState.items.push({ id: ++nextId, message, tone, open: true });
}

// Phản hồi đã hiện thông báo (object flash/errors của từng phản hồi). Ở cấp module: lần tải đầu đọc trực tiếp và sự kiện của
// Inertia cùng trỏ một object → chỉ hiện một lần, kể cả khi layout gắn lại.
const handled = new WeakSet<object>();

/** true nếu lần đầu gặp object này (đánh dấu luôn). */
export function firstTime(value: object): boolean {
    const raw = toRaw(value);
    if (handled.has(raw)) return false;
    handled.add(raw);
    return true;
}

