import type { MoneyRules, SharedProps } from '@admin/types';
import { usePage } from '@inertiajs/vue3';

/** Định dạng số tiền minor unit theo quy tắc của cửa hàng — cùng thuật toán với `MoneyFormatter::format()` ở server. */
export function formatMoney(amount: number, rules: MoneyRules): string {
    const divisor = 10 ** rules.exponent;
    const absolute = Math.abs(amount);
    const integer = String(Math.trunc(absolute / divisor)).replace(/\B(?=(\d{3})+(?!\d))/g, rules.thousandsSeparator);
    const fraction = rules.exponent > 0 ? rules.decimalSeparator + String(absolute % divisor).padStart(rules.exponent, '0') : '';

    return `${amount < 0 ? '-' : ''}${integer}${fraction} ${rules.symbol}`;
}

/** Hàm định dạng tiền theo cấu hình cửa hàng (shared props `app.money`). */
export function useMoney(): (amount: number) => string {
    const page = usePage<SharedProps>();

    return (amount: number): string => formatMoney(amount, page.props.app.money);
}
