<script setup lang="ts" generic="T">
import { computed, nextTick, ref } from 'vue';
import Draggable from 'vuedraggable';
import { type DropTarget, type Path, canDrop, heightOf, indentTarget, moveNode, outdentTarget, siblingTarget } from '../dnd/tree';

defineOptions({ name: 'Sortable' });

/**
 * Sắp xếp bằng kéo-thả dùng chung của Admin (Drag & Drop Engine): danh sách dọc, lưới, hoặc cây nhiều cấp
 * (`children-key`). Một cơ chế cho page builder, menu, bộ lọc, ảnh… thay cho nút ↑ ↓ viết riêng ở từng trang.
 * Kéo-thả do thư viện vuedraggable (SortableJS) xử lý — chỉ component này được import thư viện (như reka-ui, ADR-039);
 * trang của module và plugin dùng `Sortable` + `SortHandle`, đổi thư viện bên dưới không làm vỡ trang.
 *
 *   <Sortable v-model="form.blocks" v-slot="{ item, index, handle }">
 *       <SortHandle v-bind="handle" /> …nội dung của mục…
 *   </Sortable>
 *
 * - Kéo bằng tay nắm (chuột, cảm ứng); Esc huỷ. Bàn phím trên tay nắm: ↑ ↓ đổi chỗ (lưới: cả ← →); cây: → thụt vào
 *   mục trên, ← thụt ra sau mục cha. Mỗi lần chuyển bằng bàn phím được đọc cho trình đọc màn hình.
 * - `v-model` nhận mảng mới khi cấp gốc đổi; mảng con của cây được sửa tại chỗ (phải là dữ liệu phản ứng).
 * - Slot mặc định: `item`, `index`, `depth` (1 = gốc), `path`, `siblings` (mảng chứa mục — để xoá), `handle`.
 */
type Context = {
    group: string;
    key: (event: KeyboardEvent, path: Path, siblingCount: number, key: string) => void;
    keyOf: (item: T) => string;
};

const props = withDefaults(
    defineProps<{
        modelValue: T[];
        /** Tên thuộc tính chứa mảng con của mục → sắp xếp dạng cây. Bỏ trống = danh sách phẳng. */
        childrenKey?: string;
        /** Số cấp tối đa của cây (mặc định không giới hạn). */
        maxDepth?: number;
        /** `grid`: các mục xếp nhiều cột — phím ← → cũng đổi chỗ. */
        layout?: 'vertical' | 'grid';
        /** Khoá ổn định của mục; mặc định tự cấp cho object, dùng chính giá trị cho số/chuỗi. */
        itemKey?: (item: T) => string | number;
        /** Tên mục cho trình đọc màn hình. */
        itemLabel?: (item: T) => string;
        disabled?: boolean;
        tag?: string;
        itemTag?: string;
        listClass?: string;
        itemClass?: string;
        nestedClass?: string;
        /** Nội bộ (đệ quy của cây): ngữ cảnh của Sortable gốc và vị trí của mục cha. */
        context?: Context;
        parentPath?: Path;
    }>(),
    { layout: 'vertical', tag: 'div', itemTag: 'div', listClass: 'space-y-2', itemClass: '', nestedClass: 'mt-2 space-y-2 border-l border-slate-200 pl-6', parentPath: () => [] },
);

const emit = defineEmits<{
    'update:modelValue': [items: T[]];
    /** Sau mỗi lần thứ tự đổi (kéo-thả hoặc bàn phím). */
    change: [];
}>();

defineSlots<{
    default(scope: { item: T; index: number; depth: number; path: Path; siblings: T[]; handle: Record<string, unknown> }): unknown;
    empty(): unknown;
}>();

const isRoot = computed(() => props.context === undefined);
const nested = computed(() => props.childrenKey !== undefined);
const childrenOf = computed(() => (props.childrenKey === undefined ? null : (item: T): T[] => ((item as Record<string, unknown>)[props.childrenKey as string] as T[] | undefined) ?? []));
const depthLimit = computed(() => (nested.value ? (props.maxDepth ?? Number.POSITIVE_INFINITY) : 1));
const depth = computed(() => props.parentPath.length + 1);

const rootElement = ref<HTMLElement | null>(null);
const announcement = ref('');
const generated = new WeakMap<object, string>();

function keyOf(item: T): string {
    if (props.itemKey) return String(props.itemKey(item));
    if (typeof item !== 'object' || item === null) return String(item);
    let key = generated.get(item as object);
    if (key === undefined) {
        key = `dnd-${++counter}`;
        generated.set(item as object, key);
    }
    return key;
}

function nodeAt(path: Path): T | undefined {
    let list = props.modelValue;
    let item: T | undefined;
    for (const index of path) {
        item = list[index];
        if (item === undefined) return undefined;
        list = childrenOf.value?.(item) ?? [];
    }
    return item;
}

/** Bàn phím: chuyển mục. Cấp gốc đổi → phát mảng mới qua v-model; cấp con → sửa tại chỗ mảng con của mục cha. */
function apply(from: Path, to: DropTarget): Path | null {
    const item = nodeAt(from);
    if (item === undefined || !canDrop(from, to, heightOf(item, childrenOf.value), depthLimit.value)) return null;

    const touchesRoot = from.length === 1 || to.parent.length === 0;
    const root = touchesRoot ? [...props.modelValue] : props.modelValue;
    const moved = moveNode(root, from, to, childrenOf.value);
    if (moved === null) return null;
    if (touchesRoot) emit('update:modelValue', root);
    emit('change');

    const name = props.itemLabel?.(item) ?? 'Mục';
    announcement.value = `${name}: đã chuyển tới vị trí ${moved[moved.length - 1] + 1}${moved.length > 1 ? `, cấp ${moved.length}` : ''}.`;

    return moved;
}

function onKey(event: KeyboardEvent, path: Path, siblingCount: number, key: string): void {
    if (props.disabled || event.altKey || event.ctrlKey || event.metaKey) return;
    const grid = props.layout === 'grid';
    let target: DropTarget | null = null;
    if (event.key === 'ArrowUp' || (grid && event.key === 'ArrowLeft')) target = siblingTarget(path, -1, siblingCount);
    else if (event.key === 'ArrowDown' || (grid && event.key === 'ArrowRight')) target = siblingTarget(path, 1, siblingCount);
    else if (nested.value && event.key === 'ArrowRight') {
        const previous = nodeAt([...path.slice(0, -1), path[path.length - 1] - 1]);
        target = previous === undefined ? null : indentTarget(path, childrenOf.value?.(previous).length ?? 0);
    } else if (nested.value && event.key === 'ArrowLeft') target = outdentTarget(path);
    else return;

    event.preventDefault();
    if (target === null || apply(path, target) === null) return;
    // Mục đã đổi chỗ trong DOM: trả tiêu điểm về tay nắm của nó để bấm tiếp được.
    void nextTick(() => {
        const handles = rootElement.value?.querySelectorAll<HTMLElement>('[data-dnd-handle]') ?? [];
        [...handles].find((handle) => handle.closest<HTMLElement>('[data-dnd-key]')?.dataset.dndKey === key)?.focus();
    });
}

const own: Context = { group: `dnd-group-${++counter}`, key: onKey, keyOf };
const context = computed<Context>(() => props.context ?? own);

function handleFor(item: T, index: number): Record<string, unknown> {
    const path = [...props.parentPath, index];
    const key = context.value.keyOf(item);
    const label = props.itemLabel?.(item);
    return {
        'data-dnd-handle': '',
        disabled: props.disabled,
        'aria-label': `Sắp xếp${label ? ` ${label}` : ''}: kéo, hoặc dùng phím mũi tên`,
        title: 'Kéo để sắp xếp',
        onKeydown: (event: KeyboardEvent) => context.value.key(event, path, props.modelValue.length, key),
    };
}

/** vuedraggable hỏi trước khi cho thả: cây không vượt số cấp tối đa (tính cả các cấp bên dưới mục đang kéo). */
function allowMove(event: { to: HTMLElement; draggedContext: { element: T } }): boolean {
    const targetDepth = Number(event.to.dataset.dndDepth ?? 1);
    return targetDepth + heightOf(event.draggedContext.element, childrenOf.value) <= depthLimit.value;
}

const children = (item: T): T[] => childrenOf.value?.(item) ?? [];
</script>

<script lang="ts">
/** Bộ đếm chung của mọi Sortable trên trang: khoá mục tự cấp và tên nhóm kéo-thả không trùng nhau. */
let counter = 0;
</script>

<template>
    <div :ref="(element) => (rootElement = isRoot ? (element as HTMLElement | null) : null)" :data-dnd-root="isRoot ? '' : undefined">
        <Draggable
            v-if="modelValue.length || !isRoot"
            :model-value="isRoot ? modelValue : undefined"
            :list="isRoot ? undefined : modelValue"
            :item-key="context.keyOf"
            :tag="tag"
            :class="[isRoot ? listClass : nestedClass, !isRoot && !modelValue.length ? 'min-h-3' : '']"
            :data-dnd-depth="depth"
            handle="[data-dnd-handle]"
            :group="nested ? context.group : undefined"
            :disabled="disabled"
            :move="allowMove"
            :animation="150"
            :force-fallback="true"
            :fallback-on-body="true"
            :fallback-tolerance="4"
            :empty-insert-threshold="12"
            ghost-class="opacity-40"
            @update:model-value="(items: T[]) => emit('update:modelValue', items)"
            @change="emit('change')"
        >
            <template #item="{ element, index }: { element: T; index: number }">
                <component :is="itemTag" :class="itemClass" :data-dnd-key="context.keyOf(element)">
                    <template v-if="nested">
                        <slot :item="element" :index="index" :depth="depth" :path="[...parentPath, index]" :siblings="modelValue" :handle="handleFor(element, index)" />
                        <Sortable
                            v-if="depth < depthLimit"
                            :model-value="children(element)"
                            :children-key="childrenKey"
                            :max-depth="maxDepth"
                            :item-key="itemKey"
                            :item-label="itemLabel"
                            :disabled="disabled"
                            :tag="tag"
                            :item-tag="itemTag"
                            :item-class="itemClass"
                            :nested-class="nestedClass"
                            :context="context"
                            :parent-path="[...parentPath, index]"
                            @change="emit('change')"
                        >
                            <template #default="scope"><slot v-bind="scope" /></template>
                        </Sortable>
                    </template>
                    <slot v-else :item="element" :index="index" :depth="1" :path="[index]" :siblings="modelValue" :handle="handleFor(element, index)" />
                </component>
            </template>
        </Draggable>
        <slot v-else name="empty" />
        <p v-if="isRoot" class="sr-only" aria-live="polite" data-dnd-announcement>{{ announcement }}</p>
    </div>
</template>
