/** Lựa chọn cho Select/MultiSelect/Combobox từ bảng nhãn `{ mã: nhãn }` (thứ tự theo bảng). */
export function labelOptions(labels: Record<string, string>): Array<{ value: string; label: string }> {
    return Object.entries(labels).map(([value, label]) => ({ value, label }));
}

/** Lựa chọn từ danh sách mã, nhãn lấy theo bảng nhãn (mã chưa có nhãn hiển thị nguyên mã). */
export function codeOptions(codes: string[], labels: Record<string, string> = {}): Array<{ value: string; label: string }> {
    return codes.map((code) => ({ value: code, label: labels[code] ?? code }));
}

/** Lựa chọn từ bản ghi `{ id, label }` (danh mục, thương hiệu, kho…). */
export function recordOptions<T extends { id: number }>(records: T[], label: (record: T) => string = (record) => String((record as { label?: unknown }).label ?? record.id)): Array<{ value: number; label: string }> {
    return records.map((record) => ({ value: record.id, label: label(record) }));
}
