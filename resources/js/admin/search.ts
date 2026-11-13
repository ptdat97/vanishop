import { onBeforeUnmount, ref, type Ref } from 'vue';

/** Chuỗi để so khớp tìm kiếm: chữ thường, bỏ dấu tiếng Việt (kể cả đ → d), gộp khoảng trắng — "Hà Nội" khớp "ha noi". */
export function normalizeSearch(text: string): string {
    return text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

/** Mọi từ đã gõ đều có trong nhãn (không phân biệt dấu, thứ tự từ tuỳ ý). */
export function matchesSearch(label: string, term: string): boolean {
    const words = normalizeSearch(term).split(' ').filter(Boolean);
    if (words.length === 0) return true;
    const haystack = normalizeSearch(label);

    return words.every((word) => haystack.includes(word));
}

export interface AsyncOptions<TOption> {
    results: Ref<TOption[]>;
    loading: Ref<boolean>;
    failed: Ref<boolean>;
    /** Gọi khi người dùng gõ: chờ `delay` ms rồi tìm; kết quả của lần gõ cũ hơn bị bỏ. */
    query: (term: string) => void;
}

/** Tải lựa chọn từ server theo từ khoá (Combobox/MultiSelect `search`). */
export function useAsyncOptions<TOption>(search: (term: string) => Promise<TOption[]>, delay = 250): AsyncOptions<TOption> {
    const results = ref<TOption[]>([]) as Ref<TOption[]>;
    const loading = ref(false);
    const failed = ref(false);
    let timer: ReturnType<typeof setTimeout> | undefined;
    let sequence = 0;

    function query(term: string): void {
        clearTimeout(timer);
        const current = ++sequence;
        loading.value = true;
        timer = setTimeout(async () => {
            try {
                const found = await search(term);
                if (current === sequence) {
                    results.value = found;
                    failed.value = false;
                }
            } catch {
                if (current === sequence) failed.value = true;
            } finally {
                if (current === sequence) loading.value = false;
            }
        }, delay);
    }
    onBeforeUnmount(() => clearTimeout(timer));

    return { results, loading, failed, query };
}
