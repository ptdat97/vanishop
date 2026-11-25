<script setup lang="ts">
import { t } from '@admin/i18n';
import StatusBadge from '@admin/Components/StatusBadge.vue';
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
                <span v-else class="h-8 w-8 rounded bg-slate-100" aria-hidden="true" />
                <span class="font-medium">{{ node.name ?? node.slug }}</span>
                <span class="font-mono text-xs text-slate-400">/{{ node.slug }}</span>
                <StatusBadge v-if="node.status === 'hidden'" :label="t('catalog::admin.taxonomy.hidden')" tone="neutral" />
            </div>
            <Link v-if="canManage" :href="`${baseUrl}/${node.id}/edit`" class="text-sm text-indigo-600 hover:underline">{{ t('catalog::admin.taxonomy.edit') }}</Link>
        </div>
        <ul v-if="node.children.length">
            <CategoryTreeRow v-for="child in node.children" :key="child.id" :node="child" :base-url="baseUrl" :can-manage="canManage" />
        </ul>
    </li>
</template>
