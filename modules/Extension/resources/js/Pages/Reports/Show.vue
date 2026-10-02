<script setup lang="ts">
import FlashMessage from "@admin/Components/FlashMessage.vue";
import InsightView from "@admin/Components/Insights/InsightView.vue";
import type {
    MetricData,
    SeriesData,
    TableData,
} from "@admin/Components/Insights/types";
import PageHeader from "@admin/Components/PageHeader.vue";
import { inputClass, secondaryButton } from "@admin/styles";
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps<{
    report: { key: string; label: string; description: string };
    period: { preset: string; start: string; end: string };
    presets: Record<string, string>;
    result: {
        summary: MetricData[];
        chart: SeriesData | null;
        table: TableData;
    } | null;
}>();

const page = usePage();
const baseUrl = computed(() => page.url.split("?")[0]);
const start = ref(props.period.start);
const end = ref(props.period.end);
const errors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);
const query = computed(() =>
    new URLSearchParams(
        props.period.preset === "custom"
            ? {
                  preset: "custom",
                  start: props.period.start,
                  end: props.period.end,
              }
            : { preset: props.period.preset },
    ).toString(),
);

function choose(preset: string): void {
    if (preset === "custom") {
        router.get(
            baseUrl.value,
            { preset, start: start.value, end: end.value },
            { preserveState: true },
        );
        return;
    }
    router.get(baseUrl.value, { preset }, { preserveState: true });
}
</script>

<template>
    <Head :title="report.label" />
    <PageHeader :title="report.label" :subtitle="report.description">
        <a
            v-if="result"
            :class="secondaryButton"
            :href="`${baseUrl}/export?${query}`"
            >Xuất CSV</a
        >
    </PageHeader>
    <FlashMessage />

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <button
            v-for="(label, preset) in presets"
            v-show="preset !== 'custom'"
            :key="preset"
            :class="[
                secondaryButton,
                period.preset === preset
                    ? 'border-indigo-500 text-indigo-700'
                    : '',
            ]"
            @click="choose(preset)"
        >
            {{ label }}
        </button>
        <input
            v-model="start"
            type="date"
            :class="[inputClass, 'w-40']"
            aria-label="Từ ngày"
        />
        <span class="text-slate-400">→</span>
        <input
            v-model="end"
            type="date"
            :class="[inputClass, 'w-40']"
            aria-label="Đến ngày"
        />
        <button
            :class="[
                secondaryButton,
                period.preset === 'custom'
                    ? 'border-indigo-500 text-indigo-700'
                    : '',
            ]"
            @click="choose('custom')"
        >
            Xem
        </button>
        <span v-if="errors.period" class="text-sm text-red-600">{{
            errors.period
        }}</span>
    </div>

    <template v-if="result">
        <div
            v-if="result.summary.length"
            class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <div
                v-for="metric in result.summary"
                :key="metric.label"
                class="rounded-lg border border-slate-200 bg-white p-4"
            >
                <div class="mb-1 text-sm text-slate-500">
                    {{ metric.label }}
                </div>
                <InsightView :data="metric" />
            </div>
        </div>
        <section
            v-if="result.chart"
            class="mb-6 rounded-lg border border-slate-200 bg-white p-5"
        >
            <h2 class="mb-3 text-sm font-medium text-slate-500">
                {{ result.chart.label }}
            </h2>
            <InsightView :data="result.chart" />
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <InsightView :data="result.table" />
        </section>
    </template>
    <p v-else class="text-sm text-red-600">
        Báo cáo tạm thời không chạy được (đã ghi log). Thử lại sau hoặc kiểm tra
        plugin cung cấp báo cáo.
    </p>
</template>
