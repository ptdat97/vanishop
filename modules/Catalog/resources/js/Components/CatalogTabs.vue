<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { NavItem } from '../types';

defineProps<{ nav: NavItem[]; active: string }>();
</script>

<template>
    <div class="mb-6">
        <p class="mb-2 text-sm text-slate-500">Catalog</p>
        <nav class="flex gap-1 border-b border-slate-200">
            <Link
                v-for="item in nav"
                :key="item.key"
                :href="item.url"
                class="-mb-px border-b-2 px-3 py-2 text-sm"
                :class="item.key === active ? 'border-indigo-600 font-medium text-red-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
            >
                {{ item.label }}
            </Link>
        </nav>
    </div>
</template>
