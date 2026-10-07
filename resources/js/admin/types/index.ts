export interface StaffUser {
    id: number;
    name: string;
    email: string;
}

export interface NavigationItem {
    key: string;
    label: string;
    url: string;
}

export interface SharedProps {
    app: { name: string; locale: string };
    auth: { staff: StaffUser | null };
    navigation: NavigationItem[];
    urls: { logout?: string; media?: string | null };
    [key: string]: unknown;
}

/** Phần mở rộng màn hình Admin do plugin khai báo (ADR-030 §4.A). */
export interface ExtensionField {
    key: string;
    label: string;
    type: 'string' | 'text' | 'int' | 'bool' | 'select' | 'date';
    required: boolean;
    options: Record<string, string>;
    help: string | null;
}

export interface ExtensionSection {
    plugin: string;
    /** Khoá input (id plugin dạng slug) */
    input: string;
    key: string;
    label: string;
    fields: ExtensionField[];
    values: Record<string, ExtensionFieldValue>;
}

export interface ExtensionAction {
    key: string;
    label: string;
    confirm: boolean;
    url: string;
}

export interface ExtensionFilter {
    key: string;
    label: string;
    options: Record<string, string>;
}

export interface ExtensionList {
    columns: Array<{ key: string; label: string }>;
    values: Record<number, Record<string, string | number | boolean | null>>;
    filters: ExtensionFilter[];
    filterValues: Record<string, string>;
    actions: ExtensionAction[];
}

export interface ExtensionDetail {
    actions: ExtensionAction[];
    tabs: Array<{ key: string; label: string; rows: Array<{ label: string; value: string }> }>;
}

export type ExtensionFieldValue = string | number | boolean | null;

export type ExtensionValues = Record<string, Record<string, Record<string, ExtensionFieldValue>>>;

export function initialExtensionValues(sections: ExtensionSection[]): ExtensionValues {
    const values: ExtensionValues = {};
    for (const section of sections) {
        values[section.input] ??= {};
        const current: Record<string, ExtensionFieldValue> = {};
        for (const field of section.fields) {
            current[field.key] = section.values[field.key] ?? (field.type === 'bool' ? false : null);
        }
        values[section.input][section.key] = current;
    }

    return values;
}

/** Ảnh trong Thư viện ảnh (GET /{admin}/media/browse). */
export interface MediaItem {
    id: number;
    name: string;
    thumb_url: string;
    url: string;
    width: number | null;
    height: number | null;
    size_bytes: number;
    mime_type: string;
    folder: string;
    usages_count: number;
    created_at: string;
}
