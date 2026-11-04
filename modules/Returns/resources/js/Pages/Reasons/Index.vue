<script setup lang="ts">
import DataTable from '@admin/Components/DataTable.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import StatusBadge from '@admin/Components/StatusBadge.vue';
import { confirmAction } from '@admin/confirm';
import { useStoreLocales } from '@admin/locales';
import { inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Reason {
    id: number;
    code: string;
    names: Record<string, string>;
    position: number;
    is_active: boolean;
    store_fault: boolean;
    used: boolean;
}

/** Danh mục lý do đổi trả (§3.13-1). Lý do đã dùng: không đổi mã, không xoá — chỉ tắt. */
const props = defineProps<{ baseUrl: string; returnsUrl: string; reasons: Reason[]; canManage: boolean }>();

const { locales, defaultLocale } = useStoreLocales();
const editing = ref<Reason | null>(null);
const columns = [
    { key: 'name', label: 'Tên' },
    { key: 'code', label: 'Mã', class: 'font-mono text-xs' },
    { key: 'store_fault', label: 'Lỗi do cửa hàng' },
    { key: 'is_active', label: 'Trạng thái' },
    { key: 'actions', label: '', align: 'right' as const },
];
const blank = () => ({ code: '', position: props.reasons.length, is_active: true, store_fault: false, names: Object.fromEntries(locales.map((locale) => [locale.code, ''])) as Record<string, string> });
const form = useForm(blank());
const errors = computed(() => form.errors as Record<string, string | undefined>);

function edit(reason: Reason): void {
    editing.value = reason;
    form.defaults({ code: reason.code, position: reason.position, is_active: reason.is_active, store_fault: reason.store_fault, names: Object.fromEntries(locales.map((locale) => [locale.code, reason.names[locale.code] ?? ''])) });
    form.reset();
}

function cancel(): void {
    editing.value = null;
    form.defaults(blank());
    form.reset();
    form.clearErrors();
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: cancel };
    if (editing.value) {
        form.put(`${props.baseUrl}/${editing.value.id}`, options);
    } else {
        form.post(props.baseUrl, options);
    }
}

async function destroy(reason: Reason): Promise<void> {
    if ((await confirmAction({ title: `Xoá lý do ${reason.code}?`, confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${reason.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Lý do đổi trả" />
    <p class="mb-2 text-sm text-slate-500">Đổi trả</p>
    <PageHeader title="Lý do đổi trả" subtitle="Khách chọn khi gửi yêu cầu. Lý do đã dùng chỉ tắt được, không xoá.">
        <Link :href="returnsUrl" :class="secondaryButton">Yêu cầu đổi trả</Link>
    </PageHeader>
    <p v-if="errors.reason" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ errors.reason }}</p>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <DataTable :columns="columns" :rows="reasons" :row-label="(reason) => reason.code" empty-text="Chưa có lý do nào.">
                <template #cell-name="{ row }">{{ row.names[defaultLocale] ?? row.code }}</template>
                <template #cell-store_fault="{ row }">{{ row.store_fault ? 'Có' : '—' }}</template>
                <template #cell-is_active="{ row }"><StatusBadge :label="row.is_active ? 'Đang dùng' : 'Đã tắt'" :tone="row.is_active ? 'success' : 'neutral'" /></template>
                <template #cell-actions="{ row }">
                    <template v-if="canManage">
                        <button type="button" class="text-indigo-600 hover:underline" @click="edit(row)">Sửa</button>
                        <button v-if="!row.used" type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(row)">Xoá</button>
                    </template>
                </template>
            </DataTable>
        </div>

        <form v-if="canManage" class="space-y-3 rounded-lg border border-slate-200 bg-white p-5" @submit.prevent="submit">
            <h2 class="text-sm font-medium">{{ editing ? 'Sửa lý do' : 'Thêm lý do' }}</h2>
            <FormField v-for="locale in locales" :key="locale.code" :label="`Tên (${locale.label})` + (locale.code === defaultLocale ? ' *' : '')" :error="errors[`names.${locale.code}`]">
                <input v-model="form.names[locale.code]" :class="inputClass" />
            </FormField>
            <FormField label="Mã" hint="Chữ thường, số, gạch dưới — vd. wrong_color" :error="errors.code">
                <input v-model="form.code" :class="inputClass" :disabled="editing?.used" />
            </FormField>
            <FormField label="Thứ tự" :error="errors.position"><input v-model.number="form.position" :class="inputClass" type="number" min="0" /></FormField>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" /> Khách chọn được</label>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.store_fault" type="checkbox" /> Lỗi do cửa hàng (dùng cho phí ship trả hàng, báo cáo)</label>
            <div class="flex gap-2">
                <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
                <button v-if="editing" type="button" :class="secondaryButton" @click="cancel">Huỷ</button>
            </div>
        </form>
    </div>
</template>
