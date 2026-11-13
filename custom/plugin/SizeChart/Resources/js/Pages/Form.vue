<script setup lang="ts">
import { syncLockVersion } from '@admin/forms';
import Sortable from '@admin/Components/Sortable.vue';
import SortHandle from '@admin/Components/SortHandle.vue';
import FormField from '@admin/Components/FormField.vue';
import MediaPicker from '@admin/Components/Media/MediaPicker.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { useStoreLocales } from '@admin/locales';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import type { MediaItem } from '@admin/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Block =
    | { type: 'image'; media_id: number; alt: string; url?: string | null }
    | { type: 'text'; markdown: string }
    | { type: 'table'; rows: string[][] };

interface Rule {
    size: string;
    height_min: number | null;
    height_max: number | null;
    weight_min: number | null;
    weight_max: number | null;
    note: string;
}

const props = defineProps<{
    chart: null | {
        id: number;
        names: Record<string, string>;
        base_unit: string;
        display_unit: string | null;
        blocks: Block[];
        advisor_enabled: boolean;
        advisor_rules: Rule[];
        is_default: boolean;
        active: boolean;
        lock_version: number;
    };
    units: string[];
    can: { manage: boolean };
    urls: { base: string };
}>();

const { locales, defaultLocale } = useStoreLocales();
const form = useForm({
    names: Object.fromEntries(locales.map(({ code }) => [code, props.chart?.names[code] ?? ''])) as Record<string, string>,
    base_unit: props.chart?.base_unit ?? 'cm',
    display_unit: props.chart?.display_unit ?? (null as string | null),
    blocks: (props.chart?.blocks ?? [{ type: 'table', rows: [['Size', 'Ngực', 'Eo', 'Mông'], ['S', '', '', ''], ['M', '', '', '']] }]) as Block[],
    advisor_enabled: props.chart?.advisor_enabled ?? false,
    advisor_rules: (props.chart?.advisor_rules ?? []) as Rule[],
    is_default: props.chart?.is_default ?? false,
    active: props.chart?.active ?? true,
    lock_version: props.chart?.lock_version ?? null,
});
const errors = computed(() => form.errors as Record<string, string | undefined>);
const picking = ref(false);

function addBlock(type: Block['type']): void {
    if (type === 'image') {
        picking.value = true;
    } else if (type === 'text') {
        form.blocks.push({ type: 'text', markdown: '' });
    } else {
        form.blocks.push({ type: 'table', rows: [['Size', ''], ['S', '']] });
    }
}

function onPicked(items: MediaItem[]): void {
    for (const item of items) {
        form.blocks.push({ type: 'image', media_id: item.id, alt: '', url: item.thumb_url });
    }
}

function addRow(rows: string[][]): void {
    rows.push(rows[0].map(() => ''));
}

function addColumn(rows: string[][]): void {
    rows.forEach((row) => row.push(''));
}

function submit(): void {
    const data = (current: typeof form.data extends () => infer T ? T : never) => ({
        ...current,
        blocks: current.blocks.map((block) => (block.type === 'image' ? { type: 'image', media_id: block.media_id, alt: block.alt } : block)),
    });
    if (props.chart) {
        form.transform(data).put(`${props.urls.base}/${props.chart.id}`, { preserveScroll: true, onSuccess: syncLockVersion(form, () => props.chart?.lock_version) });
    } else {
        form.transform(data).post(props.urls.base);
    }
}

async function destroy(): Promise<void> {
    if (props.chart && (await confirmAction({ title: 'Xoá bảng size này?', message: 'Mọi gán cho sản phẩm/danh mục/thương hiệu sẽ bị bỏ.', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${props.urls.base}/${props.chart.id}`);
    }
}
</script>

<template>
    <Head :title="chart ? 'Sửa bảng size' : 'Thêm bảng size'" />
    <PageHeader :title="chart ? 'Sửa bảng size' : 'Thêm bảng size'">
        <Link :href="urls.base" :class="secondaryButton">Quay lại</Link>
        <button v-if="chart && can.manage" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-6 lg:col-span-2">
            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-medium">Nội dung</h2>
                    <div class="flex gap-2 text-sm">
                        <button type="button" :class="secondaryButton" @click="addBlock('table')">+ Bảng</button>
                        <button type="button" :class="secondaryButton" @click="addBlock('text')">+ Văn bản</button>
                        <button type="button" :class="secondaryButton" @click="addBlock('image')">+ Ảnh</button>
                    </div>
                </div>
                <p v-if="errors.blocks" class="text-sm text-red-600">{{ errors.blocks }}</p>
                <Sortable v-model="form.blocks" v-slot="{ item: block, index, handle }" item-class="space-y-2 rounded border border-slate-200 p-3">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span class="flex items-center gap-1"><SortHandle v-bind="handle" />{{ block.type === 'table' ? 'Bảng' : block.type === 'text' ? 'Văn bản (Markdown)' : 'Ảnh' }}</span>
                        <button type="button" class="text-red-600" @click="form.blocks.splice(index, 1)">Bỏ</button>
                    </div>
                    <template v-if="block.type === 'table'">
                        <div class="overflow-x-auto">
                            <div class="inline-grid items-center gap-1 text-sm" :style="{ gridTemplateColumns: `repeat(${block.rows[0].length}, 6rem) auto` }">
                                <template v-for="(row, r) in block.rows" :key="r">
                                    <input
                                        v-for="(_, c) in row"
                                        :key="c"
                                        v-model="row[c]"
                                        :class="[inputClass, r === 0 || c === 0 ? 'font-medium' : '']"
                                        :aria-label="`Hàng ${r + 1}, cột ${c + 1}`"
                                    />
                                    <button v-if="r > 0" type="button" class="pl-2 text-left text-xs text-red-600" @click="block.rows.splice(r, 1)">Bỏ hàng</button>
                                    <span v-else />
                                </template>
                            </div>
                        </div>
                        <div class="flex gap-2 text-xs">
                            <button type="button" class="underline" @click="addRow(block.rows)">+ Hàng</button>
                            <button type="button" class="underline" @click="addColumn(block.rows)">+ Cột</button>
                            <button v-if="block.rows[0].length > 2" type="button" class="underline" @click="block.rows.forEach((row) => row.pop())">− Cột cuối</button>
                        </div>
                        <p class="text-xs text-slate-500">Hàng đầu và cột đầu là tiêu đề; các ô số được đổi đơn vị khi hiển thị.</p>
                    </template>
                    <textarea v-else-if="block.type === 'text'" v-model="block.markdown" :class="inputClass" rows="4" />
                    <div v-else class="flex items-center gap-3">
                        <img v-if="block.url" :src="block.url" alt="" class="h-20 rounded" />
                        <input v-model="block.alt" :class="inputClass" placeholder="Mô tả ảnh (alt)" />
                    </div>
                </Sortable>
                <MediaPicker v-model:open="picking" title="Chọn ảnh cho bảng size" :multiple="true" @select="onPicked" />
            </section>

            <section class="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
                <label class="flex items-center gap-2 text-sm font-medium"><input v-model="form.advisor_enabled" type="checkbox" /> Gợi ý size theo chiều cao/cân nặng</label>
                <div v-if="form.advisor_enabled" class="space-y-2">
                    <div v-for="(rule, index) in form.advisor_rules" :key="index" class="grid grid-cols-7 items-center gap-2 text-sm">
                        <input v-model="rule.size" :class="inputClass" placeholder="Size" />
                        <input v-model.number="rule.height_min" :class="inputClass" type="number" placeholder="Cao từ" />
                        <input v-model.number="rule.height_max" :class="inputClass" type="number" placeholder="Cao đến" />
                        <input v-model.number="rule.weight_min" :class="inputClass" type="number" placeholder="Nặng từ" />
                        <input v-model.number="rule.weight_max" :class="inputClass" type="number" placeholder="Nặng đến" />
                        <input v-model="rule.note" :class="inputClass" placeholder="Ghi chú" />
                        <button type="button" class="text-xs text-red-600" @click="form.advisor_rules.splice(index, 1)">Bỏ</button>
                        <p v-for="field in ['size', 'height_max', 'weight_max']" v-show="errors[`advisor_rules.${index}.${field}`]" :key="field" class="col-span-7 text-xs text-red-600">{{ errors[`advisor_rules.${index}.${field}`] }}</p>
                    </div>
                    <button type="button" class="text-sm underline" @click="form.advisor_rules.push({ size: '', height_min: null, height_max: null, weight_min: null, weight_max: null, note: '' })">+ Luật</button>
                    <p class="text-xs text-slate-500">Chiều cao tính bằng cm, cân nặng bằng kg. Luật đầu tiên khớp cả hai khoảng được gợi ý.</p>
                </div>
            </section>
        </div>

        <div class="space-y-4 self-start rounded-lg border border-slate-200 bg-white p-5">
            <FormField v-for="option in locales" :key="option.code" :label="`Tên (${option.label})${option.code === defaultLocale ? ' *' : ''}`" :error="errors[`names.${option.code}`]">
                <input v-model="form.names[option.code]" :class="inputClass" />
            </FormField>
            <FormField label="Đơn vị của bảng" :error="errors.base_unit">
                <Select v-model="form.base_unit" :options="units.map((unit) => ({ value: unit, label: unit }))" />
            </FormField>
            <FormField label="Cho khách đổi sang" :error="errors.display_unit">
                <Select
                    v-model="form.display_unit"
                    :options="units.filter((unit) => unit !== form.base_unit).map((unit) => ({ value: unit, label: unit }))"
                    empty-label="Không đổi"
                    :empty-value="null"
                />
            </FormField>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_default" type="checkbox" /> Bảng mặc định của cửa hàng</label>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.active" type="checkbox" /> Đang bật</label>
            <button v-if="can.manage" type="submit" :class="primaryButton" class="w-full" :disabled="form.processing">Lưu</button>
        </div>
    </form>
</template>
