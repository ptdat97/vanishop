<script setup lang="ts">
import { t } from '@admin/i18n';
import PageHeader from '@admin/Components/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    reports: Array<{ key: string; label: string; description: string }>;
}>();
</script>

<template>
    <Head :title="t('extension::admin.reports_index.reports')" />
    <PageHeader :title="t('extension::admin.reports_index.reports')" :subtitle="t('extension::admin.reports_index.reports_provided_plugins_figures_use')" />
    <div v-if="reports.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Link
            v-for="report in reports"
            :key="report.key"
            :href="`${$page.url.split('?')[0].replace(/\/$/, '')}/${report.key}`"
            class="rounded-lg border border-slate-200 bg-white p-5 hover:border-indigo-400"
        >
            <h2 class="font-medium">{{ report.label }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ report.description }}</p>
        </Link>
    </div>
    <p v-else class="text-sm text-slate-500">{{ t('extension::admin.reports_index.no_reports_yet_enable_reports') }}</p>
</template>
