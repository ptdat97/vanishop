<script setup lang="ts">
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import type { JSONContent } from '@tiptap/core';
import { computed } from 'vue';

const props = defineProps<{ modelValue: string | null }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const maximumLength = 5000;

function documentFromValue(value: string | null): JSONContent {
    if (!value) {
        return { type: 'doc', content: [{ type: 'paragraph' }] };
    }

    try {
        const document: unknown = JSON.parse(value);
        if (document && typeof document === 'object' && 'type' in document && document.type === 'doc' && 'content' in document && Array.isArray(document.content)) {
            return document as JSONContent;
        }
    } catch {
        // Existing plain-text block content is intentionally supported.
    }

    return {
        type: 'doc',
        content: value.split('\n').map((line) => ({
            type: 'paragraph',
            ...(line ? { content: [{ type: 'text', text: line }] } : {}),
        })),
    };
}

const editor = useEditor({
    extensions: [StarterKit],
    content: documentFromValue(props.modelValue),
    onUpdate: ({ editor }) => emit('update:modelValue', JSON.stringify(editor.getJSON())),
});

const characterCount = computed(() => editor.value?.getText().length ?? 0);
</script>

<template>
    <div class="overflow-hidden rounded border border-slate-300 bg-white">
        <div class="flex flex-wrap gap-1 border-b border-slate-200 p-2" role="toolbar" aria-label="Định dạng nội dung">
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-slate-100" :aria-pressed="editor?.isActive('bold')" @click="editor?.chain().focus().toggleBold().run()">Đậm</button>
            <button type="button" class="rounded px-2 py-1 text-sm italic hover:bg-slate-100" :aria-pressed="editor?.isActive('italic')" @click="editor?.chain().focus().toggleItalic().run()">Nghiêng</button>
            <button type="button" class="rounded px-2 py-1 text-sm line-through hover:bg-slate-100" :aria-pressed="editor?.isActive('strike')" @click="editor?.chain().focus().toggleStrike().run()">Gạch</button>
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-slate-100" :aria-pressed="editor?.isActive('heading', { level: 2 })" @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">Tiêu đề</button>
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-slate-100" :aria-pressed="editor?.isActive('bulletList')" @click="editor?.chain().focus().toggleBulletList().run()">Danh sách</button>
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-slate-100" :aria-pressed="editor?.isActive('orderedList')" @click="editor?.chain().focus().toggleOrderedList().run()">Đánh số</button>
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-slate-100" :aria-pressed="editor?.isActive('blockquote')" @click="editor?.chain().focus().toggleBlockquote().run()">Trích dẫn</button>
            <button type="button" class="rounded px-2 py-1 font-mono text-sm hover:bg-slate-100" :aria-pressed="editor?.isActive('codeBlock')" @click="editor?.chain().focus().toggleCodeBlock().run()">Mã</button>
        </div>
        <EditorContent :editor="editor" class="rich-text-editor min-h-36 p-3 text-sm" />
        <p class="border-t border-slate-200 px-3 py-1 text-right text-xs text-slate-500" aria-live="polite">
            {{ characterCount }} / {{ maximumLength }}
        </p>
    </div>
</template>

<style scoped>
.rich-text-editor :deep(.tiptap) {
    min-height: 8rem;
    outline: none;
}

.rich-text-editor :deep(.tiptap p + p),
.rich-text-editor :deep(.tiptap ul),
.rich-text-editor :deep(.tiptap ol),
.rich-text-editor :deep(.tiptap blockquote),
.rich-text-editor :deep(.tiptap pre) {
    margin-top: 0.75rem;
}

.rich-text-editor :deep(.tiptap ul) {
    list-style: disc;
    padding-left: 1.5rem;
}

.rich-text-editor :deep(.tiptap ol) {
    list-style: decimal;
    padding-left: 1.5rem;
}

.rich-text-editor :deep(.tiptap blockquote) {
    border-left: 2px solid #cbd5e1;
    padding-left: 0.75rem;
}

.rich-text-editor :deep(.tiptap pre) {
    overflow-x: auto;
    background: #f1f5f9;
    padding: 0.75rem;
}
</style>
