export interface NavItem {
    key: string;
    label: string;
    url: string;
}

export interface CategoryNode {
    id: number;
    slug: string;
    name: string | null;
    status: 'active' | 'hidden';
    position: number;
    depth: number;
    image_url: string | null;
    children: CategoryNode[];
}

/** Dòng danh sách thương hiệu / bộ sưu tập (khuôn Admin chung). */
export interface TaxonomyRow {
    id: number;
    name: string | null;
    slug: string;
    status: string;
    image_url: string | null;
    products_count: number;
    code?: string;
}

/** Bản dịch theo mã ngôn ngữ của cửa hàng (`vanishop.locale.supported`). */
export type Translations<T extends string> = Partial<Record<string, Partial<Record<T, string | null>>>>;

export interface LinkGroupRow {
    type_id: number;
    name: string;
    products: Array<{ id: number; product_code: string; name: string | null }>;
}

export interface VideoRow {
    id: number;
    driver: string;
    reference: string;
    title: string | null;
    available: boolean;
    watch_url: string | null;
    thumbnail_url: string | null;
}

/** Nhãn theo mã từ `catalog::labels` (server dịch). */
export interface CatalogLabels {
    product_status: Record<string, string>;
    variant_status: Record<string, string>;
    visibility: Record<string, string>;
    attribute_kind: Record<string, string>;
}
