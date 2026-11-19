/**
 * Tra chuỗi dịch của Admin (ADR-049) — cùng cú pháp với `__()` / `trans_choice()` của Laravel:
 * khoá `namespace::nhóm.khoá`, tham số `:name` (`:Name` viết hoa chữ đầu, `:NAME` viết hoa hết), số nhiều
 * `{0} …|{1} …|[2,*] …` hoặc `một|nhiều`. Phần thuần, không phụ thuộc Inertia (test bằng node).
 */

export type TranslationLines = { [key: string]: string | TranslationLines };

/** Bản dịch theo `namespace::nhóm` (prop `adminI18n` của server). */
export type TranslationCatalog = Record<string, TranslationLines>;

/** Giá trị tham số như trong `{{ }}` của Vue: `null`/`undefined` hiện rỗng. */
export type TranslationParams = Record<string, string | number | boolean | null | undefined>;

/** Dòng dịch của khoá; không có → `null`. */
export function lookup(catalog: TranslationCatalog, key: string): string | null {
    const separator = key.indexOf('::');
    if (separator < 0) return null;
    const namespace = key.slice(0, separator);
    const [group, ...path] = key.slice(separator + 2).split('.');
    let node: string | TranslationLines | undefined = catalog[`${namespace}::${group}`];
    for (const segment of path) {
        if (node === undefined || typeof node === 'string') return null;
        node = node[segment];
    }

    return typeof node === 'string' ? node : null;
}

export function replaceParams(line: string, params: TranslationParams = {}): string {
    // Khoá dài trước: `:name_full` không bị `:name` thay mất.
    const keys = Object.keys(params).sort((a, b) => b.length - a.length);
    let result = line;
    for (const key of keys) {
        const raw = params[key];
        const value = raw === null || raw === undefined ? '' : String(raw);
        result = result
            .replaceAll(`:${key.toUpperCase()}`, value.toUpperCase())
            .replaceAll(`:${key.charAt(0).toUpperCase()}${key.slice(1)}`, value.charAt(0).toUpperCase() + value.slice(1))
            .replaceAll(`:${key}`, value);
    }

    return result;
}

/** Chọn dạng số nhiều như MessageSelector của Laravel (khoảng `{n}`, `[a,b]`, `[a,*]`; không có khoảng: 1 → dạng đầu). */
export function choose(line: string, count: number): string {
    const segments = line.split('|');
    for (const segment of segments) {
        const match = segment.match(/^\s*([{[])([^}\]]*)[}\]]\s*([\s\S]*)$/);
        if (!match) continue;
        const [, bracket, range, text] = match;
        if (bracket === '{' && Number(range) === count) return text;
        if (bracket === '[') {
            const [from, to] = range.split(',').map((part) => part.trim());
            if ((from === '*' || count >= Number(from)) && (to === '*' || to === undefined || count <= Number(to))) return text;
        }
    }
    const plain = segments.map((segment) => segment.replace(/^\s*[{[][^}\]]*[}\]]\s*/, ''));

    return plain.length > 1 && count !== 1 ? plain[1] : plain[0];
}

export function translate(catalog: TranslationCatalog, key: string, params: TranslationParams = {}): string {
    return replaceParams(lookup(catalog, key) ?? key, params);
}

export function translateChoice(catalog: TranslationCatalog, key: string, count: number, params: TranslationParams = {}): string {
    const line = lookup(catalog, key);

    return line === null ? key : replaceParams(choose(line, count), { count, ...params });
}
