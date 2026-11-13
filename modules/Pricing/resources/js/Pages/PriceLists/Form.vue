<script setup lang="ts">
import DateInput from '@admin/Components/DateInput.vue';
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import Select from '@admin/Components/Select.vue';
import { confirmAction } from '@admin/confirm';
import { codeOptions, labelOptions, recordOptions } from '@admin/options';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    baseUrl: string;
    labels: { price_list_type: Record<string, string>; price_list_status: Record<string, string> };
    priceList: null | {
        id: number;
        code: string;
        name: string;
        type: string;
        customer_group_id: number | null;
        priority: number;
        starts_at: string | null;
        ends_at: string | null;
        status: string;
        lock_version: number;
    };
    types: string[];
    customerGroups: Array<{ id: number; code: string; name: string }>;
}>();

const form = useForm({
    code: props.priceList?.code ?? '',
    name: props.priceList?.name ?? '',
    type: props.priceList?.type ?? 'base',
    customer_group_id: props.priceList?.customer_group_id ?? (null as number | null),
    priority: props.priceList?.priority ?? 0,
    starts_at: props.priceList?.starts_at ?? null,
    ends_at: props.priceList?.ends_at ?? null,
    status: props.priceList?.status ?? 'active',
    lock_version: props.priceList?.lock_version ?? null,
});

function submit(): void {
    if (props.priceList) {
        form.put(`${props.baseUrl}/${props.priceList.id}`, { preserveScroll: true });
    } else {
        form.post(props.baseUrl);
    }
}

async function destroy(): Promise<void> {
    if (props.priceList && (await confirmAction({ title: 'Xoá bảng giá?', message: 'Toàn bộ giá trong bảng cũng bị xoá.', confirmLabel: 'Xoá', tone: 'danger' })) !== false) {
        router.delete(`${props.baseUrl}/${props.priceList.id}`);
    }
}
</script>

<template>
    <Head :title="priceList ? 'Sửa bảng giá' : 'Thêm bảng giá'" />
    <PageHeader :title="priceList ? `Sửa bảng giá: ${priceList.name}` : 'Thêm bảng giá'">
        <Link :href="baseUrl" :class="secondaryButton">Quay lại</Link>
        <Link v-if="priceList" :href="`${baseUrl}/${priceList.id}/prices`" :class="secondaryButton">Nhập giá</Link>
        <button v-if="priceList" type="button" :class="dangerButton" @click="destroy">Xoá</button>
    </PageHeader>

    <form class="grid max-w-3xl gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2" @submit.prevent="submit">
        <FormField label="Tên" :error="form.errors.name"><input v-model="form.name" :class="inputClass" /></FormField>
        <FormField label="Mã" hint="Chữ thường, số, _ -. Ví dụ: base, sale-1111" :error="form.errors.code"><input v-model="form.code" :class="inputClass" /></FormField>
        <FormField label="Loại" :error="form.errors.type">
            <Select v-model="form.type" :options="codeOptions(types, labels.price_list_type)" />
        </FormField>
        <FormField label="Nhóm khách" :hint="form.type === 'member' ? 'Bắt buộc với bảng giá thành viên.' : 'Để trống = áp cho mọi khách.'" :error="form.errors.customer_group_id">
            <Select v-model="form.customer_group_id" :options="recordOptions(customerGroups, (group) => group.name)" empty-label="— Mọi khách —" :empty-value="null" />
        </FormField>
        <FormField label="Priority" hint="Cao hơn thắng. Gợi ý: base = 0, sale = 10." :error="form.errors.priority">
            <input v-model.number="form.priority" :class="inputClass" type="number" />
        </FormField>
        <FormField label="Hiệu lực từ" :error="form.errors.starts_at"><DateInput v-model="form.starts_at" with-time /></FormField>
        <FormField label="Hiệu lực đến" :error="form.errors.ends_at"><DateInput v-model="form.ends_at" with-time /></FormField>
        <FormField label="Trạng thái" :error="form.errors.status">
            <Select v-model="form.status" :options="labelOptions(labels.price_list_status)" />
        </FormField>
        <div class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button></div>
    </form>
</template>
