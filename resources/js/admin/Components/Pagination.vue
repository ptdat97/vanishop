<script setup lang="ts">
import { PaginationEllipsis, PaginationList, PaginationListItem, PaginationNext, PaginationPrev, PaginationRoot } from 'reka-ui';
import Select from '@admin/Components/Select.vue';
import { computed } from 'vue';

/** Phân trang server-side; khoảng kết quả được truyền từ paginator để không phải ước lượng. */
const props = defineProps<{ current: number; last: number; from?: number | null; to?: number | null; total?: number; perPage?: number; perPageOptions?: number[] }>();
const emit = defineEmits<{ change: [page: number]; perPageChange: [perPage: number] }>();

const item = 'min-w-9 rounded-md border px-3 py-1.5 text-sm';
const perPageChoices = computed(() => (props.perPageOptions ?? []).map((value) => ({ value, label: `${value} / trang` })));
</script>

<template>
    <div v-if="last > 1 || total !== undefined" class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-4">
            <p v-if="total !== undefined" class="text-sm text-slate-500" aria-live="polite">
                <template v-if="total === 0">Không có kết quả</template>
                <template v-else>Đang xem {{ from ?? '—' }}–{{ to ?? '—' }} trên {{ total }} kết quả</template>
            </p>
            <label v-if="perPage !== undefined && perPageOptions?.length" class="flex items-center gap-2 text-sm text-slate-600">
                <span>Số dòng</span>
                <div class="w-32">
                    <Select :model-value="perPage" :options="perPageChoices" aria-label="Số dòng mỗi trang" @change="emit('perPageChange', $event)" />
                </div>
            </label>
        </div>
        <nav v-if="last > 1" aria-label="Phân trang danh sách">
            <PaginationRoot :page="current" :total="last" :items-per-page="1" :sibling-count="1" show-edges @update:page="emit('change', $event)">
                <PaginationList v-slot="{ items }" class="flex flex-wrap items-center gap-1">
                    <PaginationPrev :class="[item, 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40']" aria-label="Trang trước">‹</PaginationPrev>
                    <template v-for="(entry, index) in items" :key="index">
                        <PaginationListItem
                            v-if="entry.type === 'page'"
                            :value="entry.value"
                            :class="[item, entry.value === current ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50']"
                        >
                            {{ entry.value }}
                        </PaginationListItem>
                        <PaginationEllipsis v-else :index="index" class="px-2 text-slate-400">…</PaginationEllipsis>
                    </template>
                    <PaginationNext :class="[item, 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40']" aria-label="Trang sau">›</PaginationNext>
                </PaginationList>
            </PaginationRoot>
        </nav>
    </div>
</template>
