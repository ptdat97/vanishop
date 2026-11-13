import { httpErrorMessage } from './toast';

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

/** Lỗi HTTP chung (hết phiên, quyền, không tìm thấy, quá tải, máy chủ) → thông điệp dễ hiểu; lỗi nghiệp vụ/validate giữ `message` của server. */
function errorMessage(status: number, data: Record<string, unknown>): string {
    if ([403, 404, 413, 419, 429].includes(status) || status >= 500 || typeof data.message !== 'string' || data.message === '') {
        return httpErrorMessage(status);
    }

    return data.message;
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
        throw new HttpError(errorMessage(response.status, data), response.status, data.errors ?? {});
    }
    return data as T;
}

/**
 * Gửi FormData có báo tiến độ tải lên (`onProgress` 0–100) — fetch không báo được tiến độ gửi nên dùng XMLHttpRequest.
 * Lỗi giống `postJson` (HttpError, 422 kèm `errors`); mất mạng → HttpError status 0.
 */
export function uploadJson<T>(url: string, body: FormData, onProgress?: (percent: number) => void): Promise<T> {
    return new Promise((resolve, reject) => {
        const request = new XMLHttpRequest();
        request.open('POST', url);
        request.withCredentials = true;
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-XSRF-TOKEN', xsrfToken());
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.upload.onprogress = (event) => {
            if (event.lengthComputable) onProgress?.(Math.round((event.loaded / event.total) * 100));
        };
        request.onload = () => {
            let data: Record<string, unknown> = {};
            try {
                data = JSON.parse(request.responseText || '{}');
            } catch {
                data = {};
            }
            if (request.status >= 200 && request.status < 300) {
                resolve(data as T);
            } else {
                reject(new HttpError(errorMessage(request.status, data), request.status, (data.errors as Record<string, string[]>) ?? {}));
            }
        };
        request.onerror = () => reject(new HttpError(httpErrorMessage(0), 0));
        request.send(body);
    });
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
        throw new HttpError(errorMessage(response.status, data), response.status, data.errors ?? {});
    }
    return data as T;
}
