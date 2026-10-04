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

const number = new Intl.NumberFormat('vi-VN');

export function formatValue(value: string | number | boolean | null | undefined, format: ValueFormat): string {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value !== 'number') return String(value);
    if (format === 'money') return `${number.format(value)} ₫`;
    if (format === 'percent') return `${number.format(Math.round(value * 10) / 10)}%`;
    return number.format(value);
}
