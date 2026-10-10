import type { SharedProps, StoreLocaleOption } from '@admin/types';
import { usePage } from '@inertiajs/vue3';

/** Ngôn ngữ bản dịch của cửa hàng (cấu hình `vanishop.locale`): danh sách + mặc định (bắt buộc có bản dịch). */
export function useStoreLocales(): { locales: StoreLocaleOption[]; defaultLocale: string } {
    const app = usePage<SharedProps>().props.app;

    return { locales: app.locales, defaultLocale: app.defaultLocale };
}

/** Một bản dịch rỗng cho mỗi ngôn ngữ, ghép giá trị đã có. */
export function translationsFor<T extends object>(locales: StoreLocaleOption[], empty: T, existing?: Partial<Record<string, object>>): Record<string, T> {
    return Object.fromEntries(locales.map(({ code }) => [code, { ...empty, ...existing?.[code] } as T]));
}
