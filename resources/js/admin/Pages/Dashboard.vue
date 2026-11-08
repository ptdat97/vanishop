<script setup lang="ts">
import InsightView from '@admin/Components/Insights/InsightView.vue';
import type { InsightData } from '@admin/Components/Insights/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    widgets: Array<{
        key: string;
        label: string;
        width: number;
        plugin: string | null;
        data: InsightData;
    }>;
    cards: Array<{ title: string; body: string }>;
    /** "Việc cần làm" (0.3.101): hàng đợi có số đếm do module góp (slot vani.admin.dashboard.tasks). */
    tasks: Array<{ key: string; label: string; count: number; url: string; tone: 'warning' | 'danger' | 'info'; hint: string | null }>;
}>();

const pending = computed(() => props.tasks.filter((task) => task.count > 0).length);
const toneClass: Record<string, string> = {
    warning: 'border-amber-300 bg-amber-50 text-amber-900',
    danger: 'border-red-300 bg-red-50 text-red-800',
    info: 'border-indigo-200 bg-indigo-50 text-indigo-900',
};

const span: Record<number, string> = {
    1: '',
    2: 'lg:col-span-2',
    3: 'sm:col-span-2 lg:col-span-3',
};
</script>

<template>
    <Head title="Tổng quan" />
    <h1 class="mb-6 text-xl font-semibold">Tổng quan</h1>
    <section v-if="tasks.length" class="mb-8" aria-labelledby="tasks-heading">
        <h2 id="tasks-heading" class="mb-3 flex items-baseline gap-2 font-semibold">
            Việc cần làm
            <span class="text-sm font-normal text-slate-500">{{ pending ? `${pending} hàng đợi đang có việc` : 'Không có việc tồn đọng' }}</span>
        </h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <Link
                v-for="task in tasks"
                :key="task.key"
                :href="task.url"
                class="flex flex-col rounded-lg border p-4 transition hover:shadow-sm"
                :class="task.count > 0 ? toneClass[task.tone] ?? toneClass.warning : 'border-slate-200 bg-white text-slate-400'"
                :data-task="task.key"
            >
                <span class="text-2xl font-semibold tabular-nums">{{ task.count }}</span>
                <span class="text-sm font-medium" :class="task.count > 0 ? '' : 'text-slate-500'">{{ task.label }}</span>
                <span v-if="task.hint" class="mt-1 text-xs opacity-75">{{ task.hint }}</span>
            </Link>
        </div>
    </section>
    <div v-if="widgets.length || cards.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <section v-for="widget in widgets" :key="widget.key" class="rounded-lg border border-slate-200 bg-white p-5" :class="span[widget.width]">
            <h2 class="mb-3 text-sm font-medium text-slate-500">
                {{ widget.label }}
            </h2>
            <InsightView :data="widget.data" />
        </section>
        <div v-for="(card, index) in cards" :key="index" class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="font-medium">{{ card.title }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ card.body }}</p>
        </div>
    </div>
    <p v-else-if="!tasks.length" class="text-sm text-slate-500">Chưa có nội dung. Bật plugin Báo cáo (vani.reports) hoặc plugin khác để thêm widget vào trang này.</p>
</template>
