import { formatMoney } from '@admin/money';
import type { MoneyRules, SharedProps } from '@admin/types';
import { usePage } from '@inertiajs/vue3';

/** Dữ liệu có kiểu do DashboardWidget/ReportProvider trả về (Modules\Extension\Contracts\Data). */
export type ValueFormat = 'number' | 'money' | 'percent' | 'text';

export type MetricData = {
    kind: 'metric';
    label: string;
    value: number;
    format: ValueFormat;
    hint: string | null;
};
export type SeriesData = {
    kind: 'series';
    label: string;
    labels: string[];
    values: number[];
    format: ValueFormat;
};
export type TableData = {
    kind: 'table';
    label: string;
    columns: Array<{ key: string; label: string; format: ValueFormat }>;
    rows: Array<Record<string, string | number | boolean | null>>;
};
export type InsightData = MetricData | SeriesData | TableData;

/** Định dạng giá trị của báo cáo theo cấu hình cửa hàng (dấu phân cách và ký hiệu tiền từ `app.money`). */
export function formatValue(value: string | number | boolean | null | undefined, format: ValueFormat, rules: MoneyRules): string {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value !== 'number') return String(value);
    if (format === 'money') return formatMoney(value, rules);
    const [integer, fraction] = String(format === 'percent' ? Math.round(value * 10) / 10 : value).split('.');
    const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, rules.thousandsSeparator) + (fraction ? rules.decimalSeparator + fraction : '');

    return format === 'percent' ? `${grouped}%` : grouped;
}

/** `formatValue` gắn sẵn quy tắc của cửa hàng (shared props). */
export function useFormatValue(): (value: string | number | boolean | null | undefined, format: ValueFormat) => string {
    const page = usePage<SharedProps>();

    return (value, format) => formatValue(value, format, page.props.app.money);
}
