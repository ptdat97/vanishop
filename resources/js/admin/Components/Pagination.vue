<script setup lang="ts">
import { PaginationEllipsis, PaginationList, PaginationListItem, PaginationNext, PaginationPrev, PaginationRoot } from 'reka-ui';

/** Phân trang theo số trang do server trả (`current`/`last`); trang cha tự tải lại khi `change`. */
defineProps<{ current: number; last: number }>();
const emit = defineEmits<{ change: [page: number] }>();

const item = 'min-w-9 rounded-md border px-3 py-1.5 text-sm';
</script>

<template>
    <PaginationRoot v-if="last > 1" :page="current" :total="last" :items-per-page="1" :sibling-count="1" show-edges class="mt-4" @update:page="emit('change', $event)">
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
</template>
