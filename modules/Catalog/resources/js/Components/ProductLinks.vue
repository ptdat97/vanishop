<script setup lang="ts">
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
const typeOptions = computed(() => props.linkTypes.map((type) => ({ value: type.id, label: `${type.label}${type.is_active ? '' : ' (đang tắt)'}` })));

function add(): void {
    const styleCodes = codes.value.split(/[\s,;]+/).filter(Boolean);
    if (typeId.value && styleCodes.length) {
        router.post(
            `${props.productUrl}/links`,
            { link_type_id: typeId.value, style_codes: styleCodes, one_way: oneWay.value },
            { preserveScroll: true, onSuccess: () => (codes.value = '') },
        );
    }
}

async function remove(group: LinkGroupRow, productId: number, code: string): Promise<void> {
    if ((await confirmAction({ title: `Gỡ liên kết "${group.name}" với ${code}?`, confirmLabel: 'Gỡ', tone: 'danger' })) !== false) {
        router.delete(`${props.productUrl}/links/${group.type_id}/${productId}`, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="text-sm font-medium">Sản phẩm liên kết</h2>

        <p v-if="!linkTypes.length" class="text-sm text-slate-500">
            Chưa có loại liên kết. Tạo ở <a :href="linkTypesUrl" class="text-indigo-600 hover:underline">Loại liên kết</a> (vd. "Sản phẩm tương tự", "Mua kèm").
        </p>
        <div v-else class="space-y-2 rounded-md bg-slate-50 p-3">
            <div class="flex flex-wrap gap-2">
                <div class="w-56"><Select v-model="typeId" :options="typeOptions" aria-label="Loại liên kết" /></div>
                <input v-model="codes" :class="inputClass" class="min-w-48 flex-1" placeholder="Mã sản phẩm, cách nhau bằng dấu phẩy" @keydown.enter.prevent="add" />
                <button type="button" :class="secondaryButton" :disabled="!typeId || !codes.trim()" @click="add">Liên kết</button>
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input v-model="oneWay" type="checkbox" />
                Một chiều: chỉ sản phẩm này gợi ý các sản phẩm kia (bỏ chọn = các sản phẩm gợi ý lẫn nhau)
            </label>
            <p v-if="errors.style_codes" class="text-sm text-red-600">{{ errors.style_codes }}</p>
        </div>

        <div v-for="group in links" :key="group.type_id">
            <h3 class="mb-1 text-xs font-medium uppercase text-slate-500">{{ group.name }}</h3>
            <ul class="divide-y divide-slate-100 rounded-md border border-slate-200 text-sm">
                <li v-for="linked in group.products" :key="linked.id" class="flex items-center justify-between px-3 py-1.5">
                    <span>{{ linked.name }} <span class="font-mono text-xs text-slate-400">{{ linked.style_code }}</span></span>
                    <button type="button" class="text-red-600 hover:underline" @click="remove(group, linked.id, linked.style_code)">Gỡ</button>
                </li>
            </ul>
        </div>
    </div>
</template>
