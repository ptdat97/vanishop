<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { BrandRef, NavItem, Translations } from '../../types';

interface ValueRow {
    code: string;
    translations: { vi: { label: string }; en: { label: string } };
}

const props = defineProps<{
    brand: BrandRef;
    nav: NavItem[];
    attribute: null | {
        id: number;
        code: string;
        kind: string;
        input_type: string;
        is_filterable: boolean;
        position: number;
        lock_version: number;
        translations: Translations<'name'>;
        values: Array<{ code: string; translations: Translations<'label'> }>;
    };
    kinds: string[];
    inputTypes: string[];
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'attributes')?.url ?? '');

const form = useForm({
    code: props.attribute?.code ?? '',
    kind: props.attribute?.kind ?? 'spec',
    input_type: props.attribute?.input_type ?? 'select',
    is_filterable: props.attribute?.is_filterable ?? false,
    position: props.attribute?.position ?? 0,
    lock_version: props.attribute?.lock_version ?? null,
    translations: {
        vi: { name: props.attribute?.translations.vi?.name ?? '' },
        en: { name: props.attribute?.translations.en?.name ?? '' },
    },
    values: (props.attribute?.values ?? []).map<ValueRow>((value) => ({
        code: value.code,
        translations: { vi: { label: value.translations.vi?.label ?? '' }, en: { label: value.translations.en?.label ?? '' } },
    })),
});

const errors = computed(() => form.errors as Record<string, string | undefined>);
const hasOptions = computed(() => form.input_type === 'select' || form.input_type === 'multiselect');

function addValue(): void {
    form.values.push({ code: '', translations: { vi: { label: '' }, en: { label: '' } } });
}

function removeValue(index: number): void {
    form.values.splice(index, 1);
}

function submit(): void {
    if (props.attribute) {
        form.put(`${baseUrl.value}/${props.attribute.id}`, { preserveScroll: true });
    } else {
        form.post(baseUrl.value);
    }
}

function destroy(): void {
    if (props.attribute && confirm('Xoá thuộc tính này?')) {
        router.delete(`${baseUrl.value}/${props.attribute.id}`);
    }
}
</script>

<template>
    <Head :title="attribute ? 'Sửa thuộc tính' : 'Thêm thuộc tính'" />
    <CatalogTabs :brand="brand" :nav="nav" active="attributes" />
    <PageHeader :title="attribute ? 'Sửa thuộc tính' : 'Thêm thuộc tính'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
        <button v-if="attribute" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <form class="max-w-3xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
            <FormField label="Tên (tiếng Việt)" :error="errors['translations.vi.name']">
                <input v-model="form.translations.vi.name" :class="inputClass" type="text" />
            </FormField>
            <FormField label="Tên (English)" :error="errors['translations.en.name']">
                <input v-model="form.translations.en.name" :class="inputClass" type="text" />
            </FormField>
            <FormField label="Mã" hint="Chữ thường, số, gạch dưới. Ví dụ: material" :error="errors.code">
                <input v-model="form.code" :class="inputClass" type="text" />
            </FormField>
            <FormField label="Thứ tự" :error="errors.position">
                <input v-model.number="form.position" :class="inputClass" type="number" min="0" />
            </FormField>
            <FormField label="Loại" :error="errors.kind">
                <select v-model="form.kind" :class="inputClass">
                    <option v-for="kind in kinds" :key="kind" :value="kind">{{ kind === 'spec' ? 'spec: hiển thị & lọc' : 'internal: chỉ nội bộ' }}</option>
                </select>
            </FormField>
            <FormField label="Kiểu nhập" :error="errors.input_type">
                <select v-model="form.input_type" :class="inputClass">
                    <option v-for="type in inputTypes" :key="type" :value="type">{{ type }}</option>
                </select>
            </FormField>
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input v-model="form.is_filterable" type="checkbox" />
                Dùng làm bộ lọc trên storefront
            </label>
        </div>

        <div v-if="hasOptions" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium">Giá trị</h2>
                <button type="button" :class="secondaryButton" @click="addValue">Thêm giá trị</button>
            </div>
            <p v-if="errors.values" class="text-sm text-red-600">{{ errors.values }}</p>
            <div v-for="(value, index) in form.values" :key="index" class="grid items-start gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
                <FormField label="Mã" :error="errors[`values.${index}.code`]">
                    <input v-model="value.code" :class="inputClass" type="text" />
                </FormField>
                <FormField label="Nhãn (vi)" :error="errors[`values.${index}.translations.vi.label`]">
                    <input v-model="value.translations.vi.label" :class="inputClass" type="text" />
                </FormField>
                <FormField label="Nhãn (en)">
                    <input v-model="value.translations.en.label" :class="inputClass" type="text" />
                </FormField>
                <button type="button" class="mt-6 text-sm text-red-600" @click="removeValue(index)">Bỏ</button>
            </div>
        </div>

        <p v-if="errors.lock_version" class="text-sm text-red-600">{{ errors.lock_version }}</p>
        <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
    </form>
</template>
