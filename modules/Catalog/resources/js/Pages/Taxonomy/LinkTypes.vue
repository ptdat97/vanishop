<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem, Translations } from '../../types';

interface LinkTypeRow {
    id: number;
    code: string;
    is_active: boolean;
    position: number;
    groups_count: number;
    translations: Translations<'name'>;
}

const props = defineProps<{ nav: NavItem[]; linkTypes: LinkTypeRow[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'link-types')?.url ?? '');
const editingId = ref<number | null>(null);
const columns = [
    { key: 'name', label: 'Tên' },
    { key: 'code', label: 'Mã', class: 'font-mono text-xs' },
    { key: 'groups_count', label: 'Nhóm liên kết' },
    { key: 'is_active', label: 'Trạng thái' },
    { key: 'actions', label: '', align: 'right' as const },
];

const { locales, defaultLocale } = useStoreLocales();
const blank = () => ({ code: '', is_active: true, position: 0, translations: translationsFor(locales, { name: '' }) });
const form = useForm(blank());
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(type: LinkTypeRow): void {
    editingId.value = type.id;
    form.defaults({ code: type.code, is_active: type.is_active, position: type.position, translations: translationsFor(locales, { name: '' }, type.translations) });
    form.reset();
}

function cancel(): void {
    editingId.value = null;
    form.defaults(blank());
    form.reset();
    form.clearErrors();
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: cancel };
    if (editingId.value) {
        form.put(`${baseUrl.value}/${editingId.value}`, options);
    } else {
        form.post(baseUrl.value, options);
    }
}

async function destroy(type: LinkTypeRow): Promise<void> {
    if ((await confirmAction({ title: `Xoá loại liên kết ${type.code}?`, confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${type.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Loại liên kết" />
    <CatalogTabs :nav="nav" active="link-types" />
    <PageHeader title="Loại liên kết" subtitle="Nhóm sản phẩm gợi ý trên trang sản phẩm: tương tự, mua kèm, nâng cấp…" />

    <p v-if="errors.link_type" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ errors.link_type }}</p>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="linkTypes" :row-label="(type) => type.code" empty-text="Chưa có loại liên kết nào.">
                <template #cell-name="{ row }">{{ row.translations[defaultLocale]?.name }}</template>
                <template #cell-is_active="{ row }"><StatusBadge :label="row.is_active ? 'Đang bật' : 'Tắt'" :tone="row.is_active ? 'success' : 'neutral'" /></template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">Sửa</button>
                        <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">Xoá</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? 'Sửa loại liên kết' : 'Thêm loại liên kết' }}</h2>
            <FormField v-for="option in locales" :key="option.code" :label="`Tên hiển thị (${option.label})`" :error="errors[`translations.${option.code}.name`]">
                <input v-model="form.translations[option.code].name" :class="inputClass" />
            </FormField>
            <FormField label="Mã" hint="Chữ thường, số, gạch ngang — vd. similar, cross-sell" :error="errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField label="Thứ tự trên trang sản phẩm" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" /> Hiện trên storefront</label>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                <button v-if="editingId" type="button" :class="secondaryButton" @click="cancel">Huỷ</button>
            </div>
        </form>
    </div>
</template>
