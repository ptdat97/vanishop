/**
 * Phép toán thuần trên danh sách và cây cho Drag & Drop Engine của Admin (component `Sortable`): kéo-thả bằng chuột/cảm ứng
 * do thư viện vuedraggable (SortableJS) xử lý; file này lo phần thư viện không có — sắp xếp bằng bàn phím và giới hạn số
 * cấp của cây. Không đụng DOM hay Vue, test bằng `npm run test:dnd`. Danh sách phẳng là cây một cấp. Vị trí của một mục
 * là `Path`: chỉ số qua từng cấp, vd. [1, 0] = con đầu của mục thứ hai.
 */
export type Path = number[];

/** Nơi thả: chèn vào danh sách con của `parent` (rỗng = gốc) tại `index`. */
export type DropTarget = { parent: Path; index: number };

/** Lấy danh sách con của một mục; `null` = danh sách phẳng (không lồng). */
export type ChildrenOf<T> = ((item: T) => T[]) | null;

export const samePath = (a: Path, b: Path): boolean => a.length === b.length && a.every((value, index) => value === b[index]);

/** `path` nằm trong nhánh của `ancestor` (hoặc chính nó). */
export const isWithin = (path: Path, ancestor: Path): boolean => path.length >= ancestor.length && ancestor.every((value, index) => value === path[index]);

export function listAt<T>(root: T[], parent: Path, childrenOf: ChildrenOf<T>): T[] | null {
    let list: T[] = root;
    for (const index of parent) {
        const item = list[index];
        if (item === undefined || childrenOf === null) return null;
        list = childrenOf(item);
    }
    return list;
}

export function nodeAt<T>(root: T[], path: Path, childrenOf: ChildrenOf<T>): T | undefined {
    return listAt(root, path.slice(0, -1), childrenOf)?.[path[path.length - 1]];
}

/** Số cấp bên dưới một mục: 0 khi không có con. */
export function heightOf<T>(item: T, childrenOf: ChildrenOf<T>): number {
    const children = childrenOf?.(item) ?? [];
    return children.length ? 1 + Math.max(...children.map((child) => heightOf(child, childrenOf))) : 0;
}

/** Thả được: không vào chính nhánh đang kéo và không vượt số cấp tối đa. */
export function canDrop(from: Path, to: DropTarget, draggedHeight: number, maxDepth: number): boolean {
    return !isWithin(to.parent, from) && to.parent.length + 1 + draggedHeight <= maxDepth;
}

/** Thả tại đây không đổi gì (ngay trước hoặc ngay sau chính nó). */
export function isNoop(from: Path, to: DropTarget): boolean {
    const index = from[from.length - 1];
    return samePath(from.slice(0, -1), to.parent) && (to.index === index || to.index === index + 1);
}

/**
 * Vị trí thả sau khi đã rút mục đang kéo ra khỏi cây: mọi chỉ số đứng sau nó trong cùng danh sách lùi một.
 */
export function afterRemoval(from: Path, to: DropTarget): DropTarget {
    const parent = from.slice(0, -1);
    const removed = from[from.length - 1];
    const level = parent.length;
    if (!isWithin(to.parent, parent)) return to;
    if (to.parent.length === level) return { parent: to.parent, index: to.index > removed ? to.index - 1 : to.index };

    const shifted = [...to.parent];
    if (shifted[level] > removed) shifted[level] -= 1;
    return { parent: shifted, index: to.index };
}

/**
 * Chuyển một mục tới vị trí mới, sửa TẠI CHỖ các mảng của cây (mảng phản ứng của Vue nhận được thay đổi). `to` tính theo
 * cây TRƯỚC khi rút mục ra. Trả về vị trí mới của mục, hoặc null khi không chuyển (đích không hợp lệ hoặc không đổi gì).
 */
export function moveNode<T>(root: T[], from: Path, to: DropTarget, childrenOf: ChildrenOf<T>): Path | null {
    const source = listAt(root, from.slice(0, -1), childrenOf);
    if (source === null || source[from[from.length - 1]] === undefined || isWithin(to.parent, from) || isNoop(from, to)) return null;

    const [item] = source.splice(from[from.length - 1], 1);
    const target = afterRemoval(from, to);
    const destination = listAt(root, target.parent, childrenOf);
    if (destination === null) {
        source.splice(from[from.length - 1], 0, item);
        return null;
    }
    const index = Math.max(0, Math.min(target.index, destination.length));
    destination.splice(index, 0, item);

    return [...target.parent, index];
}

/** Bàn phím: lên/xuống một bậc trong cùng danh sách. */
export function siblingTarget(from: Path, delta: -1 | 1, siblingCount: number): DropTarget | null {
    const index = from[from.length - 1];
    const next = index + delta;
    return next < 0 || next >= siblingCount ? null : { parent: from.slice(0, -1), index: delta === 1 ? next + 1 : next };
}

/** Bàn phím: thụt vào — thành mục con cuối của mục ngay trên. */
export function indentTarget(from: Path, previousSiblingChildCount: number): DropTarget | null {
    const index = from[from.length - 1];
    return index === 0 ? null : { parent: [...from.slice(0, -1), index - 1], index: previousSiblingChildCount };
}

/** Bàn phím: thụt ra — đứng ngay sau mục cha. */
export function outdentTarget(from: Path): DropTarget | null {
    return from.length < 2 ? null : { parent: from.slice(0, -2), index: from[from.length - 2] + 1 };
}
