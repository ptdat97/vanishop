/**
 * Hàm dịch cho các module thuần của Admin (toast.ts, upload.ts — test bằng node, không phụ thuộc Inertia). `i18n.ts` nối
 * `t()` vào khi nạp; chưa nối (test) thì trả chính khoá.
 */
export type Translator = (key: string, params?: Record<string, string | number | boolean | null | undefined>) => string;

let current: Translator = (key) => key;

export function setTranslator(translator: Translator): void {
    current = translator;
}

export function translateText(key: string, params?: Record<string, string | number | boolean | null | undefined>): string {
    return current(key, params);
}
