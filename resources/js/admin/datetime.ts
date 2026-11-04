import type { SharedProps, StoreClockRules } from '@admin/types';
import { usePage } from '@inertiajs/vue3';

export type DateTimeKind = keyof StoreClockRules['formats'];

/**
 * Định dạng thời điểm ISO 8601 theo múi giờ + mẫu của cửa hàng — cùng kết quả với `StoreClock::format()` ở server.
 * Mẫu hỗ trợ các ký tự PHP date(): d j m n Y y H G i s.
 */
export function formatDateTime(value: string | null | undefined, rules: StoreClockRules, kind: DateTimeKind = 'datetime'): string {
    const date = value ? new Date(value) : null;
    if (date === null || Number.isNaN(date.getTime())) {
        return '—';
    }
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-GB', { timeZone: rules.timezone, year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric', hourCycle: 'h23' })
            .formatToParts(date)
            .map((part) => [part.type, part.value]),
    );
    const pad = (text: string): string => text.padStart(2, '0');
    const tokens: Record<string, string> = {
        d: pad(parts.day),
        j: String(Number(parts.day)),
        m: pad(parts.month),
        n: String(Number(parts.month)),
        Y: parts.year,
        y: parts.year.slice(-2),
        H: pad(parts.hour),
        G: String(Number(parts.hour)),
        i: pad(parts.minute),
        s: pad(parts.second),
    };

    return rules.formats[kind].replace(/\\?[djmnYyHGis]/g, (token) => (token.startsWith('\\') ? token.slice(1) : tokens[token]));
}

/** Hàm định dạng thời điểm theo cấu hình cửa hàng (shared props `app.clock`). */
export function useDateTime(): (value: string | null | undefined, kind?: DateTimeKind) => string {
    const page = usePage<SharedProps>();

    return (value, kind = 'datetime') => formatDateTime(value, page.props.app.clock, kind);
}
