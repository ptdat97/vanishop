import type { ConfirmField } from '@admin/confirm';
export interface StaffUser {
    id: number;
    name: string;
    email: string;
}

export interface NavigationItem {
    key: string;
    label: string;
    url: string;
    /** Nhóm accordion trên sidebar; null = mục cấp trên cùng. */
    group: string | null;
}

export interface NavigationGroup {
    key: string;
    label: string;
}

/** Ngôn ngữ bản dịch của cửa hàng (`vanishop.locale`), mặc định đứng đầu. */
export interface StoreLocaleOption {
    code: string;
    label: string;
}

/** Quy tắc định dạng tiền của cửa hàng (`MoneyFormatter::rules()`). */
export interface MoneyRules {
    currency: string;
    exponent: number;
    thousandsSeparator: string;
    decimalSeparator: string;
    symbol: string;
    /** Đơn vị nghìn/triệu/tỷ của số rút gọn theo ngôn ngữ Admin. */
    compactUnits: string[];
}

/** Múi giờ + mẫu ngày giờ của cửa hàng (`StoreClock`; mẫu theo PHP date()). */
export interface StoreClockRules {
    timezone: string;
    formats: Record<'date' | 'datetime' | 'datetime_seconds' | 'short_datetime', string>;
}

/** Lựa chọn của Select/Combobox. */
export interface Option {
    value: string;
    label: string;
}

export interface SharedProps {
    app: { name: string; locale: string; locales: StoreLocaleOption[]; defaultLocale: string; money: MoneyRules; clock: StoreClockRules };
    auth: { staff: StaffUser | null };
    navigation: NavigationItem[];
    navigationGroups: NavigationGroup[];
    urls: { logout?: string; profile?: string; media?: string | null };
    [key: string]: unknown;
}

/** Phần mở rộng màn hình Admin do plugin khai báo (ADR-030 §4.A). */
export interface ExtensionField {
    key: string;
    label: string;
    type: 'string' | 'text' | 'int' | 'bool' | 'select' | 'date' | 'media' | 'video';
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
    /** 0.3.97: `get` = mở trang / tải file với `ids[]` trên URL (in phiếu, xuất file) thay vì POST. */
    method?: 'post' | 'get';
    /** `_blank`: mở tab mới (trang in). */
    target?: '_blank';
    /** 0.3.99: hỏi thêm giá trị trước khi gửi (vd. trạng thái mới + lý do) — gửi kèm `ids`. */
    fields?: ConfirmField[];
    /** 0.3.103: giá trị cố định gửi kèm (vd. `{status: 'active'}`). */
    data?: Record<string, string | number | boolean>;
}

export interface ExtensionFilter {
    key: string;
    label: string;
    options: Record<string, string>;
}

export interface ExtensionList {
    columns: Array<{ key: string; label: string }>;
    values: Record<number, Record<string, string | number | boolean | null>>;
    editValues?: Record<number, Record<string, number | null>>;
    urls?: Record<number, Record<string, string>>;
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
    /** 0.3.91: `video` không có ảnh thu nhỏ (`thumb_url` null) — xem bằng thẻ <video> với `url`. */
    kind?: 'image' | 'video';
    thumb_url: string | null;
    url: string;
    width: number | null;
    height: number | null;
    size_bytes: number;
    mime_type: string;
    folder: string;
    usages_count: number;
    created_at: string;
}
