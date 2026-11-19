import assert from 'node:assert/strict';
import { test } from 'node:test';
// Chạy bằng `npm run test:admin`.
import { choose, lookup, translate, translateChoice } from '../../../../node_modules/.cache/vani-admin/translate.js';

const catalog = {
    'catalog::admin': {
        title: 'Sản phẩm',
        media: { folder_exists: 'Thư mục :name đã có.', greeting: ':Name và :NAME' },
        selected: '{0} Chưa chọn|{1} Đã chọn :count sản phẩm|[2,*] Đã chọn :count sản phẩm',
        photos: ':count photo|:count photos',
    },
};

test('tra khoá namespace::nhóm.khoá lồng nhau; thiếu khoá → chính khoá', () => {
    assert.equal(lookup(catalog, 'catalog::admin.title'), 'Sản phẩm');
    assert.equal(lookup(catalog, 'catalog::admin.media'), null);
    assert.equal(translate(catalog, 'catalog::admin.media.missing'), 'catalog::admin.media.missing');
    assert.equal(translate(catalog, 'khong-co-namespace'), 'khong-co-namespace');
});

test('thay tham số như Laravel: :name, :Name, :NAME; tham số dài thay trước', () => {
    assert.equal(translate(catalog, 'catalog::admin.media.folder_exists', { name: 'Áo' }), 'Thư mục Áo đã có.');
    assert.equal(translate(catalog, 'catalog::admin.media.greeting', { name: 'lan' }), 'Lan và LAN');
    assert.equal(translate({ 'x::y': { z: ':name_full / :name' } }, 'x::y.z', { name: 'A', name_full: 'B' }), 'B / A');
});

test('số nhiều theo khoảng {n}, [a,*] và dạng một|nhiều', () => {
    assert.equal(translateChoice(catalog, 'catalog::admin.selected', 0), 'Chưa chọn');
    assert.equal(translateChoice(catalog, 'catalog::admin.selected', 1), 'Đã chọn 1 sản phẩm');
    assert.equal(translateChoice(catalog, 'catalog::admin.selected', 5), 'Đã chọn 5 sản phẩm');
    assert.equal(translateChoice(catalog, 'catalog::admin.photos', 1), '1 photo');
    assert.equal(translateChoice(catalog, 'catalog::admin.photos', 3), '3 photos');
    assert.equal(choose('Một dạng', 7), 'Một dạng');
});
