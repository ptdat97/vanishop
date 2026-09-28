<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { CategoryNode } from '../types';

defineOptions({ name: 'CategoryTreeRow' });
defineProps<{ node: CategoryNode; baseUrl: string; canManage: boolean }>();
</script>

<template>
    <li>
        <div class="flex items-center justify-between border-t border-slate-100 py-2" :style="{ paddingLeft: `${(node.depth - 1) * 1.5}rem` }">
            <div class="flex items-center gap-2">
                <img v-if="node.image_url" :src="node.image_url" alt="" class="h-8 w-8 rounded object-cover" />
                <span class="font-medium">{{ node.name ?? node.slug }}</span>
                <span class="text-xs text-slate-400">/{{ node.slug }}</span>
                <span v-if="node.status === 'hidden'" class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">ẩn</span>
            </div>
            <Link v-if="canManage" :href="`${baseUrl}/${node.id}/edit`" class="text-sm text-indigo-600 hover:underline">Sửa</Link>
        </div>
        <ul v-if="node.children.length">
            <CategoryTreeRow v-for="child in node.children" :key="child.id" :node="child" :base-url="baseUrl" :can-manage="canManage" />
        </ul>
    </li>
</template>
