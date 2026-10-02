<script setup lang="ts">
import FormField from '@admin/Components/FormField.vue';
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    baseUrl: string;
    priceList: null | {
        id: number;
        code: string;
        name: string;
        type: string;
        priority: number;
        starts_at: string | null;
        ends_at: string | null;
        status: string;
        lock_version: number;
    };
    types: string[];
}>();

const typeLabels: Record<string, string> = { base: 'Giá niêm yết (base)', sale: 'Khuyến mãi (sale)', member: 'Thành viên (member)' };
const form = useForm({
    code: props.priceList?.code ?? '',
    name: props.priceList?.name ?? '',
    type: props.priceList?.type ?? 'base',
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

function destroy(): void {
    if (props.priceList && confirm('Xoá bảng giá và toàn bộ giá trong bảng?')) {
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
            <select v-model="form.type" :class="inputClass">
                <option v-for="type in types" :key="type" :value="type">{{ typeLabels[type] ?? type }}</option>
            </select>
        </FormField>
        <FormField label="Priority" hint="Cao hơn thắng. Gợi ý: base = 0, sale = 10." :error="form.errors.priority">
            <input v-model.number="form.priority" :class="inputClass" type="number" />
        </FormField>
        <FormField label="Hiệu lực từ" :error="form.errors.starts_at"><input v-model="form.starts_at" :class="inputClass" type="datetime-local" /></FormField>
        <FormField label="Hiệu lực đến" :error="form.errors.ends_at"><input v-model="form.ends_at" :class="inputClass" type="datetime-local" /></FormField>
        <FormField label="Trạng thái" :error="form.errors.status">
            <select v-model="form.status" :class="inputClass">
                <option value="active">Đang dùng</option>
                <option value="inactive">Tắt</option>
            </select>
        </FormField>
        <p v-if="form.errors.lock_version" class="text-sm text-red-600 sm:col-span-2">{{ form.errors.lock_version }}</p>
        <div class="sm:col-span-2"><button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button></div>
    </form>
</template>
