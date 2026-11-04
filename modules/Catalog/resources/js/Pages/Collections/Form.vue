<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { labelOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { translationsFor, useStoreLocales } from '@admin/locales';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CatalogTabs from '../../Components/CatalogTabs.vue';
import type { CatalogLabels, NavItem, Translations } from '../../types';

const props = defineProps<{
    nav: NavItem[];
    labels: CatalogLabels;
    collection: null | { id: number; slug: string; status: string; position: number; translations: Translations<'name' | 'description'>; style_codes: string };
}>();

const baseUrl = computed(() => props.nav.find((item) => item.key === 'collections')?.url ?? '');
const { locales, defaultLocale } = useStoreLocales();
const form = useForm({
    slug: props.collection?.slug ?? '',
    status: props.collection?.status ?? 'active',
    position: props.collection?.position ?? 0,
    translations: translationsFor(locales, { name: '', description: '' }, props.collection?.translations),
    style_codes: props.collection?.style_codes ?? '',
});
const errors = computed(() => form.errors as Record<string, string | undefined>);

function submit(): void {
    if (props.collection) {
        form.put(`${baseUrl.value}/${props.collection.id}`, { preserveScroll: true });
    } else {
        form.post(baseUrl.value);
    }
}

async function destroy(): Promise<void> {
    if (props.collection && (await confirmAction({ title: 'Xoá bộ sưu tập này?', message: 'Sản phẩm không bị xoá.', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${baseUrl.value}/${props.collection.id}`);
    }
}
</script>

<template>
    <Head :title="collection ? 'Sửa bộ sưu tập' : 'Thêm bộ sưu tập'" />
    <CatalogTabs :nav="nav" active="collections" />
    <PageHeader :title="collection ? 'Sửa bộ sưu tập' : 'Thêm bộ sưu tập'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
        <button v-if="collection" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <form class="grid max-w-4xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField v-for="option in locales" :key="`name-${option.code}`" :label="`Tên (${option.label})${option.code === defaultLocale ? ' *' : ''}`" :error="errors[`translations.${option.code}.name`]">
            <input v-model="form.translations[option.code].name" :class="inputClass" />
        </FormField>
        <FormField v-for="option in locales" :key="`description-${option.code}`" :label="`Mô tả (${option.label})`">
            <textarea v-model="form.translations[option.code].description" :class="inputClass" rows="3" />
        </FormField>
        <FormField label="Slug" :error="errors.slug"><input v-model="form.slug" :class="inputClass" /></FormField>
        <div class="grid grid-cols-2 gap-4">
            <FormField label="Trạng thái" :error="errors.status">
                <Select v-model="form.status" :options="labelOptions(labels.visibility)" />
            </FormField>
            <FormField label="Thứ tự" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
        </div>
        <FormField class="sm:col-span-2" label="Mã sản phẩm theo thứ tự hiển thị" hint="Mỗi dòng một mã (hoặc cách nhau bằng dấu phẩy)." :error="errors.style_codes">
            <textarea v-model="form.style_codes" :class="inputClass" class="font-mono" rows="8" />
        </FormField>
        <div class="sm:col-span-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
        </div>
    </form>
</template>
