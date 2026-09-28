<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    cards: Array<{ title: string; body: string }>;
}>();
</script>

<template>
    <Head title="Tổng quan" />
    <h1 class="mb-6 text-xl font-semibold">Tổng quan</h1>
    <div v-if="cards.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="(card, index) in cards" :key="index" class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="font-medium">{{ card.title }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ card.body }}</p>
        </div>
    </div>
    <p v-else class="text-sm text-slate-500">Chưa có nội dung. Module và plugin có thể thêm card vào trang này.</p>
</template>
