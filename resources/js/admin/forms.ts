import type { FormDataErrors, FormDataType, UseFormSubmitOptions } from '@inertiajs/core';
import { router, useForm, type InertiaForm } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted } from 'vue';
import { confirmAction } from './confirm';
import { GENERAL_ERROR_KEYS, toast } from './toast';

/**
 * Admin Form Standard — mọi form tạo/sửa của module và plugin dùng chung một khuôn:
 *
 *   const { form, save } = useAdminForm({ name: props.item?.name ?? '', lock_version: props.item?.lock_version ?? null },
 *       { lockVersion: () => props.item?.lock_version });
 *   const submit = () => (props.item ? save('put', `${base}/${props.item.id}`) : save('post', base));
 *
 * - **Chống gửi lặp**: `save()` bỏ qua khi lần gửi trước chưa xong; nút Lưu vẫn `:disabled="form.processing"` để thấy đang lưu.
 * - **Lỗi validation** hiện tại ô (FormField `:error`); **lỗi nghiệp vụ** (`GENERAL_ERROR_KEYS`, gồm `lock_version`) hiện toast
 *   qua Toaster. Có lỗi theo ô → thêm một toast nhắc (ô lỗi có thể nằm ở tab ngôn ngữ/popup đang đóng).
 * - **Khoá lạc quan**: sau khi *chính form này* lưu thành công, nhận `lock_version` mới từ props (lần lưu thứ hai không báo nhầm
 *   xung đột). Tải lại trang vì thao tác khác (tải ảnh…) không đổi phiên bản trong form → xung đột thật vẫn được phát hiện.
 * - **Trạng thái sau khi lưu**: dữ liệu vừa lưu (kèm `lock_version` mới) thành giá trị gốc → `form.isDirty` về `false`.
 * - **Cảnh báo dữ liệu chưa lưu**: rời trang (link/router của Inertia, đóng hoặc tải lại tab) khi `form.isDirty` → hỏi lại
 *   (`useLeaveGuard`).
 *   Giá trị gốc chụp lại sau khi trang gắn xong, nên component chuẩn hoá giá trị lúc gắn (Select, DateInput…) không báo nhầm.
 */
export interface AdminFormOptions {
    /** Phiên bản hiện tại trên server (thường `() => props.item?.lock_version`); form phải có trường `lock_version`. */
    lockVersion?: () => number | null | undefined;
    /** Hỏi lại khi rời trang còn thay đổi chưa lưu. Mặc định bật. */
    leaveGuard?: boolean;
}

export type SaveMethod = 'post' | 'put' | 'patch';

export interface AdminForm<TForm extends object> {
    form: InertiaForm<TForm>;
    /** Gửi form; `post` (tạo mới) mặc định không giữ vị trí cuộn, `put`/`patch` (sửa) giữ. */
    save: (method: SaveMethod, url: string, options?: UseFormSubmitOptions) => void;
}

const FIELD_ERRORS_MESSAGE = 'Chưa lưu được: kiểm tra các ô báo lỗi.';

export function useAdminForm<TForm extends FormDataType<TForm>>(data: TForm, options: AdminFormOptions = {}): AdminForm<TForm> {
    const form = useForm(data) as InertiaForm<TForm>;

    onMounted(() => nextTick(() => form.defaults()));
    if (options.leaveGuard !== false) {
        useLeaveGuard(() => form.isDirty);
    }

    function save(method: SaveMethod, url: string, visit: UseFormSubmitOptions = {}): void {
        if (form.processing) {
            return;
        }

        form[method](url, {
            preserveScroll: method !== 'post',
            ...visit,
            onSuccess: async (page) => {
                syncLockVersion(form, options.lockVersion);
                await visit.onSuccess?.(page);
                // Gọi defaults() trong onSuccess thì Inertia không tự lấy dữ liệu vừa gửi làm gốc → tự làm cho cả form.
                form.defaults();
            },
            onError: (errors) => {
                reportFieldErrors(errors);
                return visit.onError?.(errors);
            },
        });
    }

    return { form, save };
}

/** Nhận `lock_version` mới từ server vào form (chỉ gọi sau khi chính form này lưu thành công). */
function syncLockVersion(form: object, current?: () => number | null | undefined): void {
    const next = current?.();
    if (typeof next === 'number' && 'lock_version' in form) {
        (form as { lock_version: number | null }).lock_version = next;
    }
}

/** Có lỗi gắn với ô nhập (không phải lỗi chung đã thành toast riêng) → một toast nhắc. */
function reportFieldErrors(errors: FormDataErrors<object> | Record<string, unknown>): void {
    if (Object.keys(errors).some((key) => !GENERAL_ERROR_KEYS.includes(key))) {
        toast(FIELD_ERRORS_MESSAGE, 'error');
    }
}

/*
 * Cảnh báo rời trang dùng chung: một bộ lắng nghe cho cả trang, nhiều nguồn (form chính, ma trận biến thể…) → chỉ một hộp hỏi.
 */
const dirtySources = new Set<() => boolean>();
let installed = false;
let leaving = false;

const hasUnsavedChanges = (): boolean => [...dirtySources].some((isDirty) => isDirty());

function installLeaveGuard(): void {
    if (installed || typeof window === 'undefined') {
        return;
    }
    installed = true;

    window.addEventListener('beforeunload', (event) => {
        if (hasUnsavedChanges()) {
            event.preventDefault();
        }
    });

    router.on('before', (event) => {
        const visit = event.detail.visit;
        // Chỉ chặn chuyển trang: bỏ qua lần gửi dữ liệu, prefetch và tải lại một phần trên cùng trang.
        if (visit.method !== 'get' || visit.prefetch || visit.only.length > 0 || !hasUnsavedChanges()) {
            return;
        }
        if (leaving) {
            leaving = false;
            return;
        }
        confirmAction({ title: 'Rời trang khi còn thay đổi chưa lưu?', message: 'Các thay đổi chưa lưu sẽ mất.', confirmLabel: 'Rời trang', tone: 'danger' }).then((answer) => {
            if (answer !== false) {
                leaving = true;
                router.visit(visit.url, { replace: visit.replace });
            }
        });

        return false;
    });
}

/**
 * Hỏi lại khi rời trang (link/router của Inertia, đóng/tải lại tab) mà `isDirty()` còn đúng. Gọi trong `setup`. Nút Back của
 * trình duyệt không qua sự kiện `before` của Inertia nên không chặn được.
 */
export function useLeaveGuard(isDirty: () => boolean): void {
    onMounted(() => {
        installLeaveGuard();
        dirtySources.add(isDirty);
    });
    onBeforeUnmount(() => dirtySources.delete(isDirty));
}
