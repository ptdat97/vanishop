<script setup lang="ts">
import InsightView from "@admin/Components/Insights/InsightView.vue";
import type { InsightData } from "@admin/Components/Insights/types";
import { Head } from "@inertiajs/vue3";

defineProps<{
    widgets: Array<{
        key: string;
        label: string;
        width: number;
        plugin: string | null;
        data: InsightData;
    }>;
    cards: Array<{ title: string; body: string }>;
}>();

const span: Record<number, string> = {
    1: "",
    2: "lg:col-span-2",
    3: "sm:col-span-2 lg:col-span-3",
};
</script>

<template>
    <Head title="Tổng quan" />
    <h1 class="mb-6 text-xl font-semibold">Tổng quan</h1>
    <div
        v-if="widgets.length || cards.length"
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
    >
        <section
            v-for="widget in widgets"
            :key="widget.key"
            class="rounded-lg border border-slate-200 bg-white p-5"
            :class="span[widget.width]"
        >
            <h2 class="mb-3 text-sm font-medium text-slate-500">
                {{ widget.label }}
            </h2>
            <InsightView :data="widget.data" />
        </section>
        <div
            v-for="(card, index) in cards"
            :key="index"
            class="rounded-lg border border-slate-200 bg-white p-5"
        >
            <h2 class="font-medium">{{ card.title }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ card.body }}</p>
        </div>
    </div>
    <p v-else class="text-sm text-slate-500">
        Chưa có nội dung. Bật plugin Báo cáo (vani.reports) hoặc plugin khác để
        thêm widget vào trang này.
    </p>
</template>
