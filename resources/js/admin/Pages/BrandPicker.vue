<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ title: string; subtitle: string; brands: Array<{ name: string; url: string }> }>();
</script>

<template>
    <Head :title="title" />
    <PageHeader :title="title" :subtitle="subtitle" />
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <Link v-for="brand in brands" :key="brand.url" :href="brand.url" class="rounded-lg border border-slate-200 bg-white p-4 font-medium hover:border-indigo-300">
            {{ brand.name }}
        </Link>
        <p v-if="!brands.length" class="text-sm text-slate-500">Bạn chưa được phân quyền cho brand nào.</p>
    </div>
</template>
