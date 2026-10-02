<script setup lang="ts">
import PageHeader from '@admin/Components/PageHeader.vue';
import { dangerButton, inputClass, primaryButton, secondaryButton } from '@admin/styles';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Template = {
    id: number;
    type: string;
    channel: string;
    locale: string;
    subject: string | null;
    body: string | null;
    meta: Record<string, unknown> | null;
    active: boolean;
    lock_version: number;
};

const props = defineProps<{
    baseUrl: string;
    types: Record<string, { label: string; variables: string[] }>;
    channels: string[];
    templates: Template[];
    can: { manage: boolean };
}>();

const editing = ref<Template | null>(null);
const creating = ref(false);
const form = useForm({
    type: 'order_placed',
    channel: 'mail',
    locale: 'vi',
    subject: '',
    body: '',
    meta_json: '',
    active: true,
    lock_version: 0,
});
const metaError = ref<string | null>(null);
const variableHint = computed(() => (props.types[form.type]?.variables ?? []).map((name) => '{' + '{ ' + name + ' }' + '}').join(', '));

function open(template: Template | null): void {
    editing.value = template;
    creating.value = template === null;
    form.defaults({
        type: template?.type ?? 'order_placed',
        channel: template?.channel ?? 'mail',
        locale: template?.locale ?? 'vi',
        subject: template?.subject ?? '',
        body: template?.body ?? '',
        meta_json: template?.meta ? JSON.stringify(template.meta, null, 2) : '',
        active: template?.active ?? true,
        lock_version: template?.lock_version ?? 0,
    });
    form.reset();
    form.clearErrors();
    metaError.value = null;
}

function submit(): void {
    let meta: Record<string, unknown> | null = null;
    try {
        meta = form.meta_json.trim() === '' ? null : JSON.parse(form.meta_json);
        metaError.value = null;
    } catch {
        metaError.value = 'JSON không hợp lệ.';
        return;
    }
    const payload = form.transform((data) => ({
        ...data,
        meta,
        meta_json: undefined,
    }));
    const options = {
        preserveScroll: true,
        onSuccess: () => ((editing.value = null), (creating.value = false)),
    };
    if (editing.value) payload.put(`${props.baseUrl}/${editing.value.id}`, options);
    else payload.post(props.baseUrl, options);
}

function remove(template: Template): void {
    if (confirm('Xoá mẫu tin này?'))
        router.delete(`${props.baseUrl}/${template.id}`, {
            preserveScroll: true,
        });
}
</script>

<template>
    <Head title="Mẫu tin" />
    <PageHeader title="Mẫu tin" subtitle="Tin giao dịch theo loại × kênh; Biến: {{ ten_bien }}.">
        <Link :href="baseUrl.replace(/templates$/, 'logs')" :class="secondaryButton">Nhật ký gửi</Link>
        <button v-if="can.manage" :class="primaryButton" @click="open(null)">Thêm mẫu</button>
    </PageHeader>

    <form v-if="creating || editing" class="mb-6 space-y-3 rounded-lg border border-slate-200 bg-white p-4 text-sm" @submit.prevent="submit">
        <div class="grid gap-3 md:grid-cols-3">
            <label>
                Loại tin
                <select v-model="form.type" :class="inputClass">
                    <option v-for="(type, code) in types" :key="code" :value="code">
                        {{ type.label }}
                    </option>
                </select>
            </label>
            <label>
                Kênh
                <input v-model="form.channel" :class="inputClass" list="notification-channels" />
                <datalist id="notification-channels">
                    <option v-for="channel in channels" :key="channel" :value="channel" />
                </datalist>
            </label>
            <label>
                Ngôn ngữ
                <select v-model="form.locale" :class="inputClass">
                    <option value="vi">Tiếng Việt</option>
                    <option value="en">English</option>
                </select>
            </label>
        </div>
        <label class="block">
            Tiêu đề (email)
            <input v-model="form.subject" :class="inputClass" />
            <span v-if="form.errors.subject" class="text-xs text-red-600">{{ form.errors.subject }}</span>
        </label>
        <label class="block">
            Nội dung
            <textarea v-model="form.body" rows="5" :class="inputClass" />
            <span v-if="form.errors.body" class="text-xs text-red-600">{{ form.errors.body }}</span>
            <span v-if="form.errors.type" class="text-xs text-red-600">{{ form.errors.type }}</span>
        </label>
        <label class="block">
            Tham số của kênh (JSON, vd. ZNS: {"template_id": "123", "params": {"order_code": "&#123;&#123; order_number &#125;&#125;"}})
            <textarea v-model="form.meta_json" rows="3" :class="[inputClass, 'font-mono text-xs']" />
            <span v-if="metaError" class="text-xs text-red-600">{{ metaError }}</span>
        </label>
        <p class="text-xs text-slate-500">Biến có sẵn: {{ variableHint }}</p>
        <label class="flex items-center gap-2"><input v-model="form.active" type="checkbox" /> Đang dùng</label>
        <p v-if="form.errors.lock_version" class="text-xs text-red-600">
            {{ form.errors.lock_version }}
        </p>
        <div class="flex gap-2">
            <button type="submit" :class="primaryButton" :disabled="form.processing">Lưu</button>
            <button type="button" :class="secondaryButton" @click="((editing = null), (creating = false))">Huỷ</button>
        </div>
    </form>

    <table class="w-full rounded-lg border border-slate-200 bg-white text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-2">Loại tin</th>
                <th class="px-4 py-2">Kênh</th>
                <th class="px-4 py-2">Nội dung</th>
                <th />
            </tr>
        </thead>
        <tbody>
            <tr v-for="template in templates" :key="template.id" class="border-t border-slate-100 align-top">
                <td class="px-4 py-2">
                    {{ types[template.type]?.label ?? template.type }}
                    <div class="font-mono text-xs text-slate-400">{{ template.type }} · {{ template.locale }}<span v-if="!template.active"> · tắt</span></div>
                </td>
                <td class="px-4 py-2 font-mono text-xs">
                    {{ template.channel }}
                </td>
                <td class="max-w-md px-4 py-2 text-xs whitespace-pre-line text-slate-600">
                    {{ template.subject ? `${template.subject}\n` : '' }}{{ template.body }}
                </td>
                <td class="px-4 py-2 text-right whitespace-nowrap">
                    <button v-if="can.manage" class="mr-3 text-indigo-600 hover:underline" @click="open(template)">Sửa</button>
                    <button v-if="can.manage" :class="dangerButton" @click="remove(template)">Xoá</button>
                </td>
            </tr>
        </tbody>
    </table>
</template>
