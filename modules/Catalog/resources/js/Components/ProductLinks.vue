<script setup lang="ts">
import { t } from '@admin/i18n';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, secondaryButton } from '@admin/styles';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { LinkGroupRow } from '../types';

const props = defineProps<{
    productUrl: string;
    links: LinkGroupRow[];
    linkTypes: Array<{ id: number; label: string; is_active: boolean }>;
    linkTypesUrl: string;
}>();

const page = usePage<{ errors: Record<string, string> }>();
const errors = computed(() => page.props.errors ?? {});
const typeId = ref<number | null>(props.linkTypes.find((type) => type.is_active)?.id ?? null);
const codes = ref('');
const oneWay = ref(false);
const typeOptions = computed(() => props.linkTypes.map((type) => ({ value: type.id, label: `${type.label}${type.is_active ? '' : t('catalog::admin.product_links.inactive')}` })));

function add(): void {
    const productCodes = codes.value.split(/[\s,;]+/).filter(Boolean);
    if (typeId.value && productCodes.length) {
        router.post(
            `${props.productUrl}/links`,
            { link_type_id: typeId.value, product_codes: productCodes, one_way: oneWay.value },
            { preserveScroll: true, onSuccess: () => (codes.value = '') },
        );
    }
}

async function remove(group: LinkGroupRow, productId: number, code: string): Promise<void> {
    if ((await confirmAction({ title: t('catalog::admin.product_links.unlink_name_from_code', { name: group.name, code }), confirmLabel: t('catalog::admin.product_links.unlink'), tone: 'danger' })) !== false) {
        router.delete(`${props.productUrl}/links/${group.type_id}/${productId}`, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="text-sm font-medium">{{ t('catalog::admin.product_links.linked_products') }}</h2>

        <p v-if="!linkTypes.length" class="text-sm text-slate-500">
            {{ t('catalog::admin.product_links.no_link_types_yet_create') }} <a :href="linkTypesUrl" class="text-indigo-600 hover:underline">{{ t('catalog::admin.product_links.link_type') }}</a> {{ t('catalog::admin.product_links.similar_products_buy_together') }}
        </p>
        <div v-else class="space-y-2 rounded-md bg-slate-50 p-3">
            <div class="flex flex-wrap gap-2">
                <div class="w-56"><Select v-model="typeId" :options="typeOptions" :aria-label="t('catalog::admin.product_links.link_type')" /></div>
                <input v-model="codes" :class="inputClass" class="min-w-48 flex-1" :placeholder="t('catalog::admin.product_links.product_codes_separated_commas')" @keydown.enter.prevent="add" />
                <button type="button" :class="secondaryButton" :disabled="!typeId || !codes.trim()" @click="add">{{ t('catalog::admin.product_links.link') }}</button>
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input v-model="oneWay" type="checkbox" />
                {{ t('catalog::admin.product_links.one_way_only_product_suggests') }}
            </label>
            <p v-if="errors.product_codes" class="text-sm text-red-600">{{ errors.product_codes }}</p>
        </div>

        <div v-for="group in links" :key="group.type_id">
            <h3 class="mb-1 text-xs font-medium uppercase text-slate-500">{{ group.name }}</h3>
            <ul class="divide-y divide-slate-100 rounded-md border border-slate-200 text-sm">
                <li v-for="linked in group.products" :key="linked.id" class="flex items-center justify-between px-3 py-1.5">
                    <span>{{ linked.name }} <span class="font-mono text-xs text-slate-400">{{ linked.product_code }}</span></span>
                    <button type="button" class="text-red-600 hover:underline" @click="remove(group, linked.id, linked.product_code)">{{ t('catalog::admin.product_links.unlink') }}</button>
                </li>
            </ul>
        </div>
    </div>
</template>
