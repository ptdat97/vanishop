import assert from 'node:assert/strict';
import { test } from 'node:test';
// Chạy bằng `npm run test:admin` (tsc biên dịch helper thuần ra node_modules/.cache/vani-admin).
import { countSelected, selectableIds, selectionState, toggleGroup, toggleOne } from '../../../../node_modules/.cache/vani-admin/skuPicker.js';

const product = { id: 1, code: 'VS-01', name: 'Đầm', variants: [{ id: 11, sku: 'A', label: 'S' }, { id: 12, sku: 'B', label: 'M' }, { id: 13, sku: 'C', label: 'L', disabled: true }] };

test('SKU chọn được bỏ SKU bị khoá; trạng thái nhóm: chưa chọn / một phần / chọn hết', () => {
    assert.deepEqual(selectableIds(product), [11, 12]);
    assert.equal(selectionState([11, 12], []), 'none');
    assert.equal(selectionState([11, 12], [12, 99]), 'some');
    assert.equal(selectionState([11, 12], [11, 12]), 'all');
    assert.equal(selectionState([], [11]), 'none');
});

test('bấm ô sản phẩm: chưa hết → chọn hết nhóm; đã hết → bỏ cả nhóm; giữ SKU của sản phẩm khác', () => {
    assert.deepEqual(toggleGroup([11, 12], [99, 12]), [99, 12, 11]);
    assert.deepEqual(toggleGroup([11, 12], [99, 11, 12]), [99]);
    assert.deepEqual(toggleOne(11, [99]), [99, 11]);
    assert.deepEqual(toggleOne(11, [99, 11]), [99]);
    assert.equal(countSelected([11, 12], [12, 99]), 1);
});
