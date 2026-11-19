<script setup lang="ts">
import { t } from '@admin/i18n';
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Size = { name: string; label: string; width: number; height: number | null; crop: boolean };
type Named = { name: string; label: string; plugin: string | null; default: Size; current: Size; overridden: boolean };

/**
 * Kích thước ảnh thu nhỏ (0.3.87): bề rộng chung (ảnh giữ tỉ lệ), tỉ lệ ảnh sản phẩm (cắt khung, mặc định 2:3), chất
 * lượng, kích thước có tên của plugin. Ảnh được tạo dần khi có người xem — không cần chạy lệnh.
 */
const props = defineProps<{
    widths: number[];
    productRatio: string;
    ratios: Array<{ value: string; label: string }>;
    quality: number;
    productSizes: Size[];
    named: Named[];
    limits: { min: number; max: number; widths: number };
    urls: { save: string; flush: string; library: string };
    canManage: boolean;
}>();

const widthsText = ref(props.widths.join(', '));
const overrides = ref<Record<string, { width: number; height: number | null; crop: boolean }>>(
    Object.fromEntries(props.named.map((size) => [size.name, { width: size.current.width, height: size.current.height, crop: size.current.crop }])),
);
const form = useForm({ widths: [] as number[], product_ratio: props.productRatio, quality: props.quality, overrides: {} as Record<string, { width: number; height: number | null; crop: boolean }> });
const errors = computed(() => form.errors as Record<string, string | undefined>);
const parsedWidths = computed(() => widthsText.value.split(/[\s,;]+/).map((value) => Number.parseInt(value, 10)).filter((value) => Number.isFinite(value)));
const preview = computed(() => {
    const [w, h] = form.product_ratio === 'original' ? [0, 0] : form.product_ratio.split(':').map(Number);
    return [...new Set(parsedWidths.value)].sort((a, b) => a - b).map((width) => (w ? `${width}×${Math.round((width * h) / w)}` : `${width}×…`));
});
const namedColumns = [
    { key: 'label', label: t('catalog::admin.media_sizes.size') },
    { key: 'plugin', label: t('catalog::admin.media_sizes.declared') },
    { key: 'size', label: t('catalog::admin.media_sizes.width_height_px') },
    { key: 'crop', label: t('catalog::admin.media_sizes.crop') },
    { key: 'reset', label: '', align: 'right' as const },
];

function reset(size: Named): void {
    overrides.value[size.name] = { width: size.default.width, height: size.default.height, crop: size.default.crop };
}

function save(): void {
    // Không có chiều cao thì không cắt khung được (thu theo tỉ lệ gốc).
    const sizes = Object.fromEntries(Object.entries(overrides.value).map(([name, size]) => [name, { ...size, height: size.height || null, crop: Boolean(size.height) && size.crop }]));
    form.transform((data) => ({ ...data, widths: parsedWidths.value, overrides: sizes })).put(props.urls.save, { preserveScroll: true });
}

async function flush(): Promise<void> {
    const confirmed = await confirmAction({
        title: t('catalog::admin.media_sizes.clear_whole_image_cache'),
        message: t('catalog::admin.media_sizes.thumbnails_regenerated_gradually_people_view'),
        confirmLabel: t('catalog::admin.media_sizes.clear_cache'),
        tone: 'danger',
    });
    if (confirmed !== false) router.post(props.urls.flush, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('catalog::admin.media_sizes.image_sizes')" />
    <PageHeader :title="t('catalog::admin.media_sizes.image_sizes')" :subtitle="t('catalog::admin.media_sizes.thumbnails_generated_first_view_then')">
        <Link :href="urls.library" :class="secondaryButton">{{ t('catalog::admin.media_sizes.media_library') }}</Link>
        <button v-if="canManage" type="button" :class="secondaryButton" @click="flush">{{ t('catalog::admin.media_sizes.clear_image_cache') }}</button>
    </PageHeader>

    <form class="grid max-w-4xl gap-6" @submit.prevent="save">
        <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 md:grid-cols-2">
            <FormField :label="t('catalog::admin.media_sizes.product_image_ratio')" :hint="t('catalog::admin.media_sizes.product_gallery_images_cropped_centred')" :error="errors.product_ratio">
                <Select v-model="form.product_ratio" :options="ratios" :disabled="!canManage" />
            </FormField>
            <FormField :label="t('catalog::admin.media_sizes.quality_30_100')" :hint="t('catalog::admin.media_sizes.text_80_balances_file_size_sharpness')" :error="errors.quality">
                <input v-model.number="form.quality" type="number" min="30" max="100" :class="inputClass" :disabled="!canManage" />
            </FormField>
            <FormField :label="t('catalog::admin.media_sizes.widths_px_most_widths', { widths: limits.widths })" :hint="t('catalog::admin.media_sizes.separated_commas_browsers_pick_version')" :error="errors.widths ?? errors['widths.0']" class="md:col-span-2">
                <input v-model="widthsText" :class="inputClass" placeholder="160, 400, 800, 1600" :disabled="!canManage" />
            </FormField>
            <p class="text-sm text-slate-600 md:col-span-2" data-product-sizes>
                {{ t('catalog::admin.media_sizes.product_images') }} <span v-for="size in preview" :key="size" class="mr-2 inline-block rounded bg-slate-100 px-2 py-0.5 font-mono text-xs">{{ size }}</span>
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-sm font-medium">{{ t('catalog::admin.media_sizes.plugin_specific_sizes') }}</h2>
            <DataTable :columns="namedColumns" :rows="named" :row-key="(row) => row.name" :row-label="(row) => row.label" :empty-text="t('catalog::admin.media_sizes.no_plugin_declares_own_sizes')">
                <template #cell-label="{ row }">
                    <div class="font-medium">{{ row.label }}</div>
                    <div class="font-mono text-xs text-slate-500">{{ row.name }}</div>
                </template>
                <template #cell-plugin="{ row }">{{ row.plugin ?? 'Core' }}</template>
                <template #cell-size="{ row }">
                    <div class="flex items-center gap-1">
                        <input v-model.number="overrides[row.name].width" type="number" :min="limits.min" :max="limits.max" :class="[inputClass, 'w-24 py-1']" :aria-label="t('catalog::admin.media_sizes.width_label', { label: row.label })" :disabled="!canManage" />
                        <span>×</span>
                        <input v-model.number="overrides[row.name].height" type="number" :min="limits.min" :max="limits.max" :class="[inputClass, 'w-24 py-1']" :placeholder="t('catalog::admin.media_sizes.auto')" :aria-label="`Cao ${row.label}`" :disabled="!canManage" />
                    </div>
                    <p v-if="errors[`overrides.${row.name}`]" class="mt-1 text-xs text-red-600">{{ errors[`overrides.${row.name}`] }}</p>
                </template>
                <template #cell-crop="{ row }">
                    <input v-model="overrides[row.name].crop" type="checkbox" :aria-label="t('catalog::admin.media_sizes.crop_label', { label: row.label })" :disabled="!canManage || !overrides[row.name].height" />
                </template>
                <template #cell-reset="{ row }">
                    <button v-if="canManage" type="button" class="text-xs text-indigo-600 hover:underline" @click="reset(row)">{{ t('catalog::admin.media_sizes.default_width_height', { width: row.default.width, height: row.default.height ?? '…' }) }}</button>
                </template>
            </DataTable>
        </section>

        <div v-if="canManage"><button type="submit" :class="primaryButton" :disabled="form.processing">{{ t('catalog::admin.media_sizes.save') }}</button></div>
    </form>
</template>
