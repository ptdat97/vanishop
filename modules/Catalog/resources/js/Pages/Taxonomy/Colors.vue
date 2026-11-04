<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions } from '@admin/options';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { NavItem, Translations } from '../../types';

interface ColorRow {
    id: number;
    code: string;
    color_family: string;
    hex: string | null;
    position: number;
    translations: Translations<'name'>;
}

const props = defineProps<{ nav: NavItem[]; colors: ColorRow[]; families: string[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'colors')?.url ?? '');
const editingId = ref<number | null>(null);
const columns = [
    { key: 'name', label: 'Màu' },
    { key: 'code', label: 'Mã', class: 'font-mono text-xs' },
    { key: 'color_family', label: 'Nhóm' },
    { key: 'actions', label: '', align: 'right' as const },
];

const { locales, defaultLocale } = useStoreLocales();
const form = useForm({ code: '', color_family: 'white', hex: '' as string | null, position: 0, translations: translationsFor(locales, { name: '' }) });
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(color: ColorRow): void {
    editingId.value = color.id;
    form.defaults({
        code: color.code,
        color_family: color.color_family,
        hex: color.hex,
        position: color.position,
        translations: translationsFor(locales, { name: '' }, color.translations),
    });
    form.reset();
}

function cancel(): void {
    editingId.value = null;
    form.defaults({ code: '', color_family: 'white', hex: '', position: 0, translations: translationsFor(locales, { name: '' }) });
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

async function destroy(color: ColorRow): Promise<void> {
    if ((await confirmAction({ title: `Xoá màu ${color.code}?`, confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${color.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Màu" />
    <CatalogTabs :nav="nav" active="colors" />
    <PageHeader title="Màu" subtitle="Nhóm màu dùng cho bộ lọc trên storefront." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="colors" :row-label="(color) => color.code" empty-text="Chưa có màu nào.">
                <template #cell-name="{ row }">
                    <span class="flex items-center gap-2">
                        <span class="inline-block h-4 w-4 rounded-full border border-slate-300" :style="{ background: row.hex ?? 'transparent' }" />
                        {{ row.translations[defaultLocale]?.name }}
                    </span>
                </template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">Sửa</button>
                        <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">Xoá</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? 'Sửa màu' : 'Thêm màu' }}</h2>
            <FormField v-for="option in locales" :key="option.code" :label="`Tên (${option.label})`" :error="errors[`translations.${option.code}.name`]">
                <input v-model="form.translations[option.code].name" :class="inputClass" />
            </FormField>
            <FormField label="Mã" hint="Chữ in hoa, ví dụ IVR" :error="errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField label="Nhóm màu" :error="errors.color_family">
                <Select v-model="form.color_family" :options="codeOptions(families)" />
            </FormField>
            <FormField label="Mã hex" hint="#FFFFF0" :error="errors.hex"><input v-model="form.hex" :class="inputClass" /></FormField>
            <FormField label="Thứ tự" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                <button v-if="editingId" type="button" :class="secondaryButton" @click="cancel">Huỷ</button>
            </div>
        </form>
    </div>
</template>
