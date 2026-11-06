<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FlashMessage from '@admin/Components/FlashMessage.vue';
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
    { key: 'label', label: 'Kích thước' },
    { key: 'plugin', label: 'Khai báo bởi' },
    { key: 'size', label: 'Rộng × cao (px)' },
    { key: 'crop', label: 'Cắt khung' },
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
        title: 'Xoá toàn bộ cache ảnh?',
        message: 'Bản thu nhỏ được tạo lại dần khi có người xem (trang đầu tiên sẽ chậm hơn một chút). Ảnh gốc không bị ảnh hưởng.',
        confirmLabel: 'Xoá cache',
        tone: 'danger',
    });
    if (confirmed !== false) router.post(props.urls.flush, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Kích thước ảnh" />
    <PageHeader title="Kích thước ảnh" subtitle="Bản thu nhỏ tạo tự động khi có người xem lần đầu, sau đó trả thẳng file tĩnh. Đổi kích thước là đổi đường dẫn ảnh — không lo ảnh cũ.">
        <Link :href="urls.library" :class="secondaryButton">Thư viện ảnh</Link>
        <button v-if="canManage" type="button" :class="secondaryButton" @click="flush">Xoá cache ảnh</button>
    </PageHeader>
    <FlashMessage />

    <form class="grid max-w-4xl gap-6" @submit.prevent="save">
        <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 md:grid-cols-2">
            <FormField label="Tỉ lệ ảnh sản phẩm" hint="Ảnh trong gallery sản phẩm được cắt (giữa ảnh) về đúng tỉ lệ để lưới đều nhau. Logo, banner, bài viết giữ tỉ lệ gốc." :error="errors.product_ratio">
                <Select v-model="form.product_ratio" :options="ratios" :disabled="!canManage" />
            </FormField>
            <FormField label="Chất lượng (30–100)" hint="80 là cân bằng giữa dung lượng và độ nét." :error="errors.quality">
                <input v-model.number="form.quality" type="number" min="30" max="100" :class="inputClass" :disabled="!canManage" />
            </FormField>
            <FormField :label="`Các bề rộng (px, tối đa ${limits.widths})`" hint="Cách nhau bằng dấu phẩy. Trình duyệt tự chọn bản vừa màn hình (srcset); ảnh nhỏ hơn bề rộng thì không phóng to." :error="errors.widths ?? errors['widths.0']" class="md:col-span-2">
                <input v-model="widthsText" :class="inputClass" placeholder="160, 400, 800, 1600" :disabled="!canManage" />
            </FormField>
            <p class="text-sm text-slate-600 md:col-span-2" data-product-sizes>
                Ảnh sản phẩm: <span v-for="size in preview" :key="size" class="mr-2 inline-block rounded bg-slate-100 px-2 py-0.5 font-mono text-xs">{{ size }}</span>
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-sm font-medium">Kích thước riêng của plugin</h2>
            <DataTable :columns="namedColumns" :rows="named" :row-key="(row) => row.name" :row-label="(row) => row.label" empty-text="Chưa có plugin nào khai báo kích thước riêng.">
                <template #cell-label="{ row }">
                    <div class="font-medium">{{ row.label }}</div>
                    <div class="font-mono text-xs text-slate-500">{{ row.name }}</div>
                </template>
                <template #cell-plugin="{ row }">{{ row.plugin ?? 'Core' }}</template>
                <template #cell-size="{ row }">
                    <div class="flex items-center gap-1">
                        <input v-model.number="overrides[row.name].width" type="number" :min="limits.min" :max="limits.max" :class="[inputClass, 'w-24 py-1']" :aria-label="`Rộng ${row.label}`" :disabled="!canManage" />
                        <span>×</span>
                        <input v-model.number="overrides[row.name].height" type="number" :min="limits.min" :max="limits.max" :class="[inputClass, 'w-24 py-1']" placeholder="tự động" :aria-label="`Cao ${row.label}`" :disabled="!canManage" />
                    </div>
                    <p v-if="errors[`overrides.${row.name}`]" class="mt-1 text-xs text-red-600">{{ errors[`overrides.${row.name}`] }}</p>
                </template>
                <template #cell-crop="{ row }">
                    <input v-model="overrides[row.name].crop" type="checkbox" :aria-label="`Cắt khung ${row.label}`" :disabled="!canManage || !overrides[row.name].height" />
                </template>
                <template #cell-reset="{ row }">
                    <button v-if="canManage" type="button" class="text-xs text-indigo-600 hover:underline" @click="reset(row)">Mặc định ({{ row.default.width }}×{{ row.default.height ?? '…' }})</button>
                </template>
            </DataTable>
        </section>

        <div v-if="canManage"><button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button></div>
    </form>
</template>
