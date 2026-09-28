<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { BrandRef, NavItem, Translations } from '../../types';

interface ColorRow {
    id: number;
    code: string;
    color_family: string;
    hex: string | null;
    position: number;
    translations: Translations<'name'>;
}

const props = defineProps<{ brand: BrandRef; nav: NavItem[]; colors: ColorRow[]; families: string[]; canManage: boolean }>();
const baseUrl = computed(() => props.nav.find((item) => item.key === 'colors')?.url ?? '');
const editingId = ref<number | null>(null);

const form = useForm({ code: '', color_family: 'white', hex: '' as string | null, position: 0, translations: { vi: { name: '' }, en: { name: '' } } });
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(color: ColorRow): void {
    editingId.value = color.id;
    form.defaults({
        code: color.code,
        color_family: color.color_family,
        hex: color.hex,
        position: color.position,
        translations: { vi: { name: color.translations.vi?.name ?? '' }, en: { name: color.translations.en?.name ?? '' } },
    });
    form.reset();
}

function cancel(): void {
    editingId.value = null;
    form.defaults({ code: '', color_family: 'white', hex: '', position: 0, translations: { vi: { name: '' }, en: { name: '' } } });
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

function destroy(color: ColorRow): void {
    if (confirm(`Xoá màu ${color.code}?`)) {
        router.delete(`${baseUrl.value}/${color.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="`Màu · ${brand.name}`" />
    <CatalogTabs :brand="brand" :nav="nav" active="colors" />
    <PageHeader title="Màu" subtitle="Tên màu do brand đặt; nhóm màu dùng để lọc chung giữa các brand." />

    <div class="grid gap-6 lg:grid-cols-3">
        <table class="w-full rounded-lg border border-slate-200 bg-white text-sm lg:col-span-2">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Màu</th>
                    <th class="px-4 py-2">Mã</th>
                    <th class="px-4 py-2">Nhóm</th>
                    <th />
                </tr>
            </thead>
            <tbody>
                <tr v-for="color in colors" :key="color.id" class="border-t border-slate-100">
                    <td class="flex items-center gap-2 px-4 py-2">
                        <span class="inline-block h-4 w-4 rounded-full border border-slate-300" :style="{ background: color.hex ?? 'transparent' }" />
                        {{ color.translations.vi?.name }}
                    </td>
                    <td class="px-4 py-2 font-mono text-xs">{{ color.code }}</td>
                    <td class="px-4 py-2">{{ color.color_family }}</td>
                    <td class="px-4 py-2 text-right">
                        <template v-if="canManage">
                            <button type="button" class="text-indigo-600 hover:underline" @click="edit(color)">Sửa</button>
                            <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(color)">Xoá</button>
                        </template>
                    </td>
                </tr>
                <tr v-if="!colors.length">
                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có màu nào.</td>
                </tr>
            </tbody>
        </table>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editingId ? 'Sửa màu' : 'Thêm màu' }}</h2>
            <FormField label="Tên (vi)" :error="errors['translations.vi.name']"><input v-model="form.translations.vi.name" :class="inputClass" /></FormField>
            <FormField label="Tên (en)"><input v-model="form.translations.en.name" :class="inputClass" /></FormField>
            <FormField label="Mã" hint="Chữ in hoa, ví dụ IVR" :error="errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
            <FormField label="Nhóm màu" :error="errors.color_family">
                <select v-model="form.color_family" :class="inputClass">
                    <option v-for="family in families" :key="family" :value="family">{{ family }}</option>
                </select>
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
