import { usePage } from '@inertiajs/vue3';
import { setTranslator } from './textTranslator';
import { lookup, translate, translateChoice, type TranslationCatalog, type TranslationParams } from './translate';

/**
 * Chuỗi giao diện Admin theo ngôn ngữ của nhân viên (ADR-049): đọc nhóm `*::admin` của file lang PHP (once prop
 * `adminI18n`, đổi khi đổi ngôn ngữ). Dùng trong template và script: `t(khoá, { tham số })`, `tc(khoá, số lượng)` với
 * khoá dạng `catalog::admin.products.title`.
 * Khoá thiếu: hiện chính khoá (môi trường dev cảnh báo ở console). TranslationKeysTest kiểm mọi khoá có `vi` + `en`.
 */
function catalog(): TranslationCatalog {
    // Trước khi app Inertia khởi tạo (module thuần gọi lúc import) chưa có trang: catalog rỗng, không lỗi.
    return (usePage().props as { adminI18n?: TranslationCatalog } | undefined)?.adminI18n ?? {};
}

function warnMissing(key: string): void {
    if (import.meta.env?.DEV && lookup(catalog(), key) === null) console.warn(`[i18n] thiếu khoá dịch: ${key}`);
}

export function t(key: string, params?: TranslationParams): string {
    warnMissing(key);

    return translate(catalog(), key, params);
}

export function tc(key: string, count: number, params?: TranslationParams): string {
    warnMissing(key);

    return translateChoice(catalog(), key, count, params);
}

// Module thuần (toast.ts, upload.ts) dịch qua textTranslator — nối t() của Admin.
setTranslator(t);
