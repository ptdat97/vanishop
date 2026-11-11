import assert from 'node:assert/strict';
import { test } from 'node:test';
// Chạy bằng `npm run test:dnd`: tsc biên dịch tree.ts ra node_modules/.cache/vani-dnd rồi node --test chạy file này.
import { afterRemoval, canDrop, heightOf, indentTarget, moveNode, outdentTarget, siblingTarget } from '../../../../node_modules/.cache/vani-dnd/tree.js';

const node = (id, children = []) => ({ id, children });
const childrenOf = (item) => item.children;
const shape = (nodes) => nodes.map((item) => item.id + (item.children.length ? `(${shape(item.children)})` : '')).join(' ');
const tree = () => [node('a', [node('a1'), node('a2')]), node('b'), node('c', [node('c1', [node('c1x')])])];

test('danh sách phẳng: kéo xuống, kéo lên, thả ngay cạnh chính nó thì không đổi', () => {
    const list = ['a', 'b', 'c', 'd'];
    assert.deepEqual(moveNode(list, [0], { parent: [], index: 3 }, null), [2]);
    assert.deepEqual(list, ['b', 'c', 'a', 'd']);
    assert.deepEqual(moveNode(list, [3], { parent: [], index: 0 }, null), [0]);
    assert.deepEqual(list, ['d', 'b', 'c', 'a']);
    assert.equal(moveNode(list, [1], { parent: [], index: 1 }, null), null);
    assert.equal(moveNode(list, [1], { parent: [], index: 2 }, null), null);
    assert.deepEqual(list, ['d', 'b', 'c', 'a']);
});

test('cây: chuyển giữa các cấp, chỉ số của đích lùi khi mục bị rút đứng trước nó', () => {
    const root = tree();
    // a2 ra gốc, đứng sau b.
    assert.deepEqual(moveNode(root, [0, 1], { parent: [], index: 2 }, childrenOf), [2]);
    assert.equal(shape(root), 'a(a1) b a2 c(c1(c1x))');
    // a vào trong c (đích [3] tính trước khi rút a → sau khi rút là [2]).
    assert.deepEqual(moveNode(root, [0], { parent: [3], index: 0 }, childrenOf), [2, 0]);
    assert.equal(shape(root), 'b a2 c(a(a1) c1(c1x))');
});

test('không thả được vào chính nhánh đang kéo; đích không tồn tại thì giữ nguyên cây', () => {
    const root = tree();
    assert.equal(moveNode(root, [2], { parent: [2, 0], index: 0 }, childrenOf), null);
    assert.equal(moveNode(root, [0], { parent: [9], index: 0 }, childrenOf), null);
    assert.equal(shape(root), shape(tree()));
});

test('afterRemoval: chỉ lùi chỉ số đứng sau mục bị rút trong cùng danh sách', () => {
    assert.deepEqual(afterRemoval([1], { parent: [], index: 3 }), { parent: [], index: 2 });
    assert.deepEqual(afterRemoval([1], { parent: [], index: 1 }), { parent: [], index: 1 });
    assert.deepEqual(afterRemoval([0], { parent: [2, 0], index: 1 }), { parent: [1, 0], index: 1 });
    assert.deepEqual(afterRemoval([2, 0], { parent: [0], index: 1 }), { parent: [0], index: 1 });
});

test('giới hạn số cấp tính cả các cấp bên dưới mục đang kéo', () => {
    const [a, , c] = tree();
    assert.equal(heightOf(a, childrenOf), 1);
    assert.equal(heightOf(c, childrenOf), 2);
    assert.equal(canDrop([2], { parent: [1], index: 0 }, 2, 3), false);
    assert.equal(canDrop([0], { parent: [1], index: 0 }, 1, 3), true);
    assert.equal(canDrop([0], { parent: [1], index: 0 }, 0, 1), false);
});

test('bàn phím: lên/xuống trong cùng cấp, thụt vào mục trên, thụt ra sau mục cha', () => {
    const root = tree();
    assert.equal(siblingTarget([0], -1, 3), null);
    assert.equal(siblingTarget([2], 1, 3), null);
    assert.deepEqual(moveNode(root, [0], siblingTarget([0], 1, 3), childrenOf), [1]);
    assert.equal(shape(root), 'b a(a1 a2) c(c1(c1x))');
    assert.equal(indentTarget([0], 0), null);
    assert.deepEqual(moveNode(root, [1], indentTarget([1], 0), childrenOf), [0, 0]);
    assert.equal(shape(root), 'b(a(a1 a2)) c(c1(c1x))');
    assert.equal(outdentTarget([0]), null);
    assert.deepEqual(moveNode(root, [0, 0, 1], outdentTarget([0, 0, 1]), childrenOf), [0, 1]);
    assert.equal(shape(root), 'b(a(a1) a2) c(c1(c1x))');
});
