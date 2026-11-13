import assert from 'node:assert/strict';
import { test } from 'node:test';
// Chạy bằng `npm run test:admin`: tsc biên dịch helper thuần (không cần trình duyệt) ra node_modules/.cache/vani-admin.
import { matchesSearch, normalizeSearch } from '../../../../node_modules/.cache/vani-admin/search.js';
import { checkFile, matchesAccept, partitionFiles } from '../../../../node_modules/.cache/vani-admin/upload.js';

const file = (name, type, kb) => new File([new Uint8Array(kb * 1024)], name, { type });

test('tìm không dấu: "ha noi" khớp "Hà Nội", đ → d, từ theo thứ tự bất kỳ', () => {
    assert.equal(normalizeSearch('  Đà   Nẵng '), 'da nang');
    assert.ok(matchesSearch('Thành phố Hà Nội', 'ha noi'));
    assert.ok(matchesSearch('Phường Đống Đa', 'da dong'));
    assert.ok(matchesSearch('Bất kỳ', ''));
    assert.ok(!matchesSearch('Hải Phòng', 'ha noi'));
});

test('tệp tải lên: đúng loại theo MIME, image/* hoặc đuôi tệp; quá dung lượng bị từ chối có lý do', () => {
    assert.ok(matchesAccept(file('a.csv', 'text/csv', 1), '.csv,text/csv'));
    assert.ok(matchesAccept(file('A.CSV', '', 1), '.csv'));
    assert.ok(matchesAccept(file('a.webp', 'image/webp', 1), 'image/*'));
    assert.ok(!matchesAccept(file('a.gif', 'image/gif', 1), 'image/jpeg,image/png'));
    assert.ok(matchesAccept(file('x.bin', 'application/octet-stream', 1), ''));

    assert.equal(checkFile(file('ok.png', 'image/png', 5), { accept: 'image/png', maxKb: 10 }), null);
    assert.match(checkFile(file('big.png', 'image/png', 11), { accept: 'image/png', maxKb: 10 }), /big\.png: 11 KB, vượt giới hạn 10 KB/);
    assert.match(checkFile(file('a.gif', 'image/gif', 1), { accept: 'image/png' }), /loại tệp không được hỗ trợ/);

    const { valid, errors } = partitionFiles([file('a.png', 'image/png', 1), file('b.gif', 'image/gif', 1)], { accept: 'image/png' });
    assert.deepEqual(valid.map((item) => item.name), ['a.png']);
    assert.equal(errors.length, 1);
});
