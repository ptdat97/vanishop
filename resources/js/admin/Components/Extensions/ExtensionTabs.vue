<script setup lang="ts">
import type { ExtensionDetail } from '@admin/types';
import { ref } from 'vue';

/** Tab do plugin khai báo trên trang chi tiết (dòng nhãn/giá trị). */
const props = defineProps<{ tabs: ExtensionDetail['tabs'] }>();
const active = ref(props.tabs[0]?.key ?? '');
</script>

<template>
    <section v-if="tabs.length" class="rounded-lg border border-slate-200 bg-white">
        <nav class="flex gap-1 border-b border-slate-100 px-2" role="tablist">
            <button v-for="tab in tabs" :key="tab.key" type="button" role="tab" :aria-selected="active === tab.key" class="px-3 py-2 text-sm" :class="active === tab.key ? 'border-b-2 border-indigo-600 font-medium' : 'text-slate-500'" @click="active = tab.key">
                {{ tab.label }}
            </button>
        </nav>
        <template v-for="tab in tabs" :key="tab.key">
            <dl v-show="active === tab.key" class="space-y-1 p-4 text-sm" role="tabpanel">
                <div v-for="(row, index) in tab.rows" :key="index" class="flex justify-between gap-3"><dt class="text-slate-500">{{ row.label }}</dt><dd class="text-right">{{ row.value }}</dd></div>
                <p v-if="!tab.rows.length" class="text-slate-500">Chưa có dữ liệu.</p>
            </dl>
        </template>
    </section>
</template>
