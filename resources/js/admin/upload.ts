/**
 * Tải tệp lên trong Admin: kiểm tra loại/dung lượng **trước khi gửi** (báo ngay, không chờ server) — server vẫn kiểm lại.
 *   const error = checkFile(file, { accept: 'image/jpeg,image/png', maxKb: 10240 }); // null khi hợp lệ
 * `accept` theo cú pháp thuộc tính `accept` của `<input type="file">`: đuôi tệp (`.csv`), MIME (`image/png`) hoặc `image/*`.
 */
export interface FileRules {
    accept?: string | string[];
    maxKb?: number | null;
}

function acceptList(accept: FileRules['accept']): string[] {
    return (Array.isArray(accept) ? accept : (accept ?? '').split(','))
        .map((item) => item.trim().toLowerCase())
        .filter(Boolean);
}

export function matchesAccept(file: File, accept: FileRules['accept']): boolean {
    const list = acceptList(accept);
    if (list.length === 0) return true;
    const name = file.name.toLowerCase();
    const type = file.type.toLowerCase();

    return list.some((item) => (item.startsWith('.') ? name.endsWith(item) : item.endsWith('/*') ? type.startsWith(item.slice(0, -1)) : type === item));
}

export function formatSize(bytes: number): string {
    return bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1).replace(/\.0$/, '')} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

/** Lý do tệp không hợp lệ (tiếng của Admin), hoặc `null`. */
export function checkFile(file: File, rules: FileRules): string | null {
    if (!matchesAccept(file, rules.accept)) {
        return `${file.name}: loại tệp không được hỗ trợ.`;
    }
    if (rules.maxKb && file.size > rules.maxKb * 1024) {
        return `${file.name}: ${formatSize(file.size)}, vượt giới hạn ${formatSize(rules.maxKb * 1024)}.`;
    }

    return null;
}

/** Tách tệp hợp lệ và lý do từ chối. */
export function partitionFiles(files: File[], rules: FileRules): { valid: File[]; errors: string[] } {
    const valid: File[] = [];
    const errors: string[] = [];
    for (const file of files) {
        const error = checkFile(file, rules);
        if (error) errors.push(error);
        else valid.push(file);
    }

    return { valid, errors };
}
