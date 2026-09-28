<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{ message: string }>();
</script>

<template>
    <Head title="Hello World" />
    <h1 class="text-xl font-semibold">{{ message }}</h1>
    <p class="mt-2 text-sm text-slate-600">Trang này thuộc plugin <code>vani.hello-world</code>.</p>
</template>
