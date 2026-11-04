/** Slug kebab-case không dấu từ một chuỗi (gợi ý slug theo tên): "Áo sơ mi Linen" → "ao-so-mi-linen". */
export function slugify(text: string): string {
    return text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[đĐ]/g, 'd')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 160);
}
