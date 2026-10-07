/**
 * Gọi JSON từ trang Admin ngoài Inertia (tải ảnh, xem trước…): gửi kèm X-XSRF-TOKEN từ cookie của Laravel.
 * Lỗi validate (422) → ném HttpError với `errors` theo field.
 */
export class HttpError extends Error {
    constructor(
        message: string,
        public status: number,
        public errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export async function postJson<T>(url: string, body: FormData | Record<string, unknown>): Promise<T> {
    const isForm = body instanceof FormData;
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(isForm ? {} : { 'Content-Type': 'application/json' }),
        },
        body: isForm ? body : JSON.stringify(body),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new HttpError(data.message ?? `HTTP ${response.status}`, response.status, data.errors ?? {});
    }
    return data as T;
}

export async function getJson<T>(url: string, query: Record<string, string | number | null | undefined> = {}): Promise<T> {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(query)) {
        if (value !== null && value !== undefined && value !== '') {
            params.set(key, String(value));
        }
    }
    const response = await fetch(params.size ? `${url}?${params}` : url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new HttpError(data.message ?? `HTTP ${response.status}`, response.status, data.errors ?? {});
    }
    return data as T;
}
