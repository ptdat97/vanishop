import { reactive, toRaw } from 'vue';

/**
 * Thông báo dạng toast của Admin (một chỗ cho mọi trang và plugin; `Toaster` gắn một lần trong AdminLayout):
 *   toast('Đã lưu.')                       // thành công: tự ẩn sau 4 giây
 *   toast('Không kết nối được.', 'error')  // lỗi/cảnh báo: giữ tới khi đóng
 *
 * Hợp đồng lỗi (server → Admin):
 * - Lỗi gắn với ô nhập: khoá theo tên trường (`name`, `translations.vi.name`…) → hiện tại ô (FormField `:error`).
 * - Lỗi chung (nghiệp vụ, xung đột phiên bản, không thuộc ô nào): khoá `business` (BusinessRuleViolation, khuyến nghị cho
 *   code mới) hoặc khoá trong `GENERAL_ERROR_KEYS` → toast lỗi, mỗi thông điệp một toast. Plugin dùng khoá riêng thì gọi
 *   `registerGeneralErrorKeys('khoa')` một lần.
 * - Lỗi HTTP không phải phản hồi Inertia (419 hết phiên, 403, 404, 413, 429, 5xx) và mất mạng → toast dễ hiểu
 *   (`httpErrorMessage`), thay cho hộp lỗi mặc định của Inertia.
 * - Thành công: `->with('success'|'warning'|'error'|'info', …)` ở server → flash `toasts` của Inertia.
 * Tối đa `MAX_TOASTS` toast cùng lúc; đầy thì bỏ toast thành công/thông tin cũ nhất trước, rồi tới cũ nhất.
 */
export type ToastTone = 'success' | 'error' | 'warning' | 'info';

export interface ToastItem {
    id: number;
    message: string;
    tone: ToastTone;
    open: boolean;
}

export const MAX_TOASTS = 5;

/** Khoá lỗi chung (không thuộc ô nhập nào) hiện thành toast lỗi. */
export const GENERAL_ERROR_KEYS: string[] = [
    'business', 'product', 'lock_version', 'category', 'link_type', 'campaign', 'changes', 'option_type', 'option_value',
];

/** Plugin khai báo thêm khoá lỗi chung của mình (gọi một lần khi nạp trang của plugin). */
export function registerGeneralErrorKeys(...keys: string[]): void {
    for (const key of keys) {
        if (key && !GENERAL_ERROR_KEYS.includes(key)) GENERAL_ERROR_KEYS.push(key);
    }
}

/** Thông điệp của các lỗi chung trong một túi lỗi (chuỗi hoặc mảng chuỗi), theo thứ tự khoá. */
export function generalErrorMessages(errors: Record<string, unknown>): string[] {
    return GENERAL_ERROR_KEYS.flatMap((key) => {
        const value = errors[key];
        const list = Array.isArray(value) ? value : [value];
        return list.filter((message): message is string => typeof message === 'string' && message !== '');
    });
}

/** Thông điệp cho lỗi HTTP ngoài luồng Inertia (status 0 = mất mạng). */
export function httpErrorMessage(status: number): string {
    if (status === 0) return 'Không kết nối được máy chủ. Kiểm tra mạng rồi thử lại.';
    if (status === 419) return 'Phiên làm việc đã hết hạn. Tải lại trang (F5) rồi thử lại.';
    if (status === 403) return 'Bạn không có quyền thực hiện thao tác này.';
    if (status === 404) return 'Không tìm thấy dữ liệu (có thể đã bị xoá).';
    if (status === 413) return 'Tệp vượt giới hạn tải lên của máy chủ.';
    if (status === 429) return 'Thao tác quá nhanh, thử lại sau ít phút.';
    if (status >= 500) return 'Máy chủ gặp lỗi, thử lại sau. Nếu lỗi lặp lại, hãy báo quản trị.';

    return `Không thực hiện được (mã lỗi ${status}).`;
}

export const toastState = reactive<{ items: ToastItem[] }>({ items: [] });
let nextId = 0;

export function toast(message: string, tone: ToastTone = 'success'): void {
    if (!message) return;
    // Bỏ toast đã đóng; cùng nội dung đang hiện thì không lặp (toast mới thay, đưa xuống cuối).
    const items = toastState.items.filter((item) => item.open && !(item.message === message && item.tone === tone));
    items.push({ id: ++nextId, message, tone, open: true });
    while (items.length > MAX_TOASTS) {
        const passive = items.findIndex((item) => item.tone === 'success' || item.tone === 'info');
        items.splice(passive >= 0 && passive < items.length - 1 ? passive : 0, 1);
    }
    toastState.items = items;
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
