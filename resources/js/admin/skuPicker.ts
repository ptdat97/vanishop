/**
 * Logic chọn của SkuPicker (thuần, không phụ thuộc Vue — test bằng `npm run test:admin`).
 *
 * Hợp đồng dữ liệu cho module cung cấp nguồn tìm (`search(term) => Promise<SkuPickerProduct[]>`, thường là một endpoint JSON
 * `{ data: SkuPickerProduct[] }` của module dùng picker): mỗi sản phẩm kèm mọi SKU của nó; `meta` là chữ phụ do module tự
 * định dạng (giá, tồn, "đã có trong bảng"…); SKU `disabled` hiện nhưng không chọn được.
 */
export interface SkuPickerVariant {
    id: number;
    sku: string;
    /** Tổ hợp tuỳ chọn, vd. "Đỏ / M". */
    label: string;
    /** `active` | `inactive`…; khác `active` hiện nhãn "Ngừng bán". */
    status?: string;
    meta?: string | null;
    disabled?: boolean;
}

export interface SkuPickerProduct {
    id: number;
    code: string;
    name: string;
    meta?: string | null;
    variants: SkuPickerVariant[];
}

export type SelectionState = 'none' | 'some' | 'all';

/** SKU chọn được của một sản phẩm (bỏ SKU `disabled`). */
export function selectableIds(product: SkuPickerProduct): number[] {
    return product.variants.filter((variant) => !variant.disabled).map((variant) => variant.id);
}

/** Trạng thái ô chọn của một nhóm SKU: chưa chọn / chọn một phần (ô gạch ngang) / chọn hết. */
export function selectionState(ids: number[], selected: readonly number[]): SelectionState {
    if (ids.length === 0) return 'none';
    const chosen = new Set(selected);
    const count = ids.filter((id) => chosen.has(id)).length;

    return count === 0 ? 'none' : count === ids.length ? 'all' : 'some';
}

/** Bấm ô chọn của một nhóm: đang chọn hết → bỏ cả nhóm; còn lại → chọn hết nhóm. Giữ nguyên SKU ngoài nhóm. */
export function toggleGroup(ids: number[], selected: readonly number[]): number[] {
    if (selectionState(ids, selected) === 'all') {
        const remove = new Set(ids);

        return selected.filter((id) => !remove.has(id));
    }

    return [...new Set([...selected, ...ids])];
}

export function toggleOne(id: number, selected: readonly number[]): number[] {
    return selected.includes(id) ? selected.filter((value) => value !== id) : [...selected, id];
}

/** Số SKU đã chọn trong một nhóm. */
export function countSelected(ids: number[], selected: readonly number[]): number {
    const chosen = new Set(selected);

    return ids.filter((id) => chosen.has(id)).length;
}
