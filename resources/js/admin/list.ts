import { router } from '@inertiajs/vue3';
import { ref, type Ref } from 'vue';

/** Trạng thái sắp xếp của danh sách (DataTable `:sort`, server `ListSort`). */
export interface ListSortState {
    key: string;
    direction: 'asc' | 'desc';
}

/** Bấm tiêu đề cột: chưa sắp → tăng dần → giảm dần → về thứ tự mặc định (`null`). */
export function nextSort(current: ListSortState | null | undefined, key: string): ListSortState | null {
    if (current?.key !== key) return { key, direction: 'asc' };
    return current.direction === 'asc' ? { key, direction: 'desc' } : null;
}

type QueryValue = string | number | boolean | null | undefined | QueryValue[] | { [key: string]: QueryValue };

/** Bỏ giá trị rỗng khỏi query (URL gọn, server nhận mặc định); `page` 1 cũng bỏ. */
export function cleanQuery(query: Record<string, QueryValue>): Record<string, QueryValue> {
    const result: Record<string, QueryValue> = {};
    for (const [key, value] of Object.entries(query)) {
        if (value === null || value === undefined || value === '' || (key === 'page' && Number(value) <= 1)) continue;
        if (Array.isArray(value) && value.length === 0) continue;
        if (typeof value === 'object' && !Array.isArray(value)) {
            const nested = cleanQuery(value);
            if (Object.keys(nested).length) result[key] = nested;
            continue;
        }
        result[key] = value;
    }

    return result;
}

export interface ListQuery {
    loading: Ref<boolean>;
    /** Tải lại danh sách với query mới; đổi bộ lọc/sắp xếp mà không truyền `page` → về trang 1. */
    visit: (query: Record<string, QueryValue>) => void;
    /** Query cho DataTable `@sort`: gộp `sort`/`direction` vào query hiện tại của trang. */
    sortQuery: (sort: ListSortState | null) => { sort?: string; direction?: string };
}

/**
 * Điều hướng danh sách Admin (lọc, sắp xếp, phân trang ở server) — giữ trạng thái trang, vị trí cuộn, thay lịch sử:
 *   const list = useListQuery(() => props.baseUrl);
 *   const search = (page = 1) => list.visit({ q: q.value, status: status.value, ...list.sortQuery(props.sort), page });
 *   <DataTable :sort="sort" :loading="list.loading.value" @sort="(next) => list.visit({ …bộ lọc, ...list.sortQuery(next) })" />
 */
export function useListQuery(url: () => string): ListQuery {
    const loading = ref(false);

    return {
        loading,
        visit(query) {
            router.get(url(), cleanQuery(query) as Record<string, string>, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => (loading.value = true),
                onFinish: () => (loading.value = false),
            });
        },
        sortQuery: (sort) => (sort ? { sort: sort.key, direction: sort.direction } : {}),
    };
}
