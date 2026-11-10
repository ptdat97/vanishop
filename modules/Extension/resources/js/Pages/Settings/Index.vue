<script setup lang="ts">
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
    <Head title="Cấu hình" />
    <PageHeader
        title="Cấu hình"
        subtitle="Chọn một nhóm để xem và chỉnh sửa cấu hình."
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
        Chưa có cấu hình nào được đăng ký.
    </div>
</template>
