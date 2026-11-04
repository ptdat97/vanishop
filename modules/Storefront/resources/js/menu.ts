import type { InjectionKey } from 'vue';

export interface LinkValue {
    type: string;
    value: string;
    label: Record<string, string>;
}

export interface MenuItem {
    id: string;
    link: LinkValue | null;
    label: Record<string, string>;
    badge: 'new' | 'sale' | null;
    image_media_id: number | null;
    enabled: boolean;
    visible_from: string | null;
    visible_to: string | null;
    children: MenuItem[];
}

export interface LinkTypeOption {
    type: string;
    label: string;
    searchable: boolean;
    options: Array<{ value: string; label: string }>;
}

/** Dữ liệu dùng chung cho cây menu trong trình sửa (loại link, nhãn đối tượng đã chọn, ảnh xem trước). */
export interface MenuEditorContext {
    linkTypes: LinkTypeOption[];
    searchUrl: string;
    /** id mục → nhãn hiện tại của đối tượng link trỏ tới. */
    linkLabels: Record<string, { label: string; exists: boolean }>;
    /** id ảnh → URL xem trước. */
    images: Record<number, string>;
}

export const menuEditorKey: InjectionKey<MenuEditorContext> = Symbol('menu-editor');

export function newMenuItem(): MenuItem {
    return {
        id: Math.random().toString(36).slice(2, 14),
        link: null,
        label: {},
        badge: null,
        image_media_id: null,
        enabled: true,
        visible_from: null,
        visible_to: null,
        children: [],
    };
}
