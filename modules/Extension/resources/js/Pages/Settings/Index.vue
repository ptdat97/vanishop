<script setup lang="ts">
import { t } from '@admin/i18n';
import PageHeader from '@admin/Components/PageHeader.vue';
import { secondaryButton } from '@admin/styles';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    cards: {
        namespace: string;
        title: string;
        subtitle: string;
        url: string;
    }[];
}>();
</script>

<template>
    <Head :title="t('extension::admin.settings_index.settings')" />
    <PageHeader
        :title="t('extension::admin.settings_index.settings')"
        :subtitle="t('extension::admin.settings_index.choose_group_view_edit_settings')"
    />

    <div v-if="cards.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Link
            v-for="card in cards"
            :key="card.namespace"
            :href="card.url"
            :class="['group flex min-h-32 items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-xs transition hover:border-slate-300 hover:shadow-sm', secondaryButton]"
        >
            <span class="min-w-0">
                <span class="block text-base font-semibold text-slate-900">{{ card.title }}</span>
                <span class="mt-1 block truncate font-mono text-xs text-slate-500">{{ card.subtitle }}</span>
            </span>
            <span class="shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-slate-700" aria-hidden="true">→</span>
        </Link>
    </div>
    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
        {{ t('extension::admin.settings_index.no_settings_registered') }}
    </div>
</template>
