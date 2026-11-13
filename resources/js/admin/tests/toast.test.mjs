import assert from 'node:assert/strict';
import { beforeEach, test } from 'node:test';
// Chạy bằng `npm run test:admin`.
import { generalErrorMessages, httpErrorMessage, MAX_TOASTS, registerGeneralErrorKeys, toast, toastState } from '../../../../node_modules/.cache/vani-admin/toast.js';

const shown = () => toastState.items.map((item) => `${item.tone}:${item.message}`);

beforeEach(() => {
    toastState.items = [];
});

test('nhiều toast: cùng nội dung không lặp; quá giới hạn bỏ toast thành công cũ trước, giữ toast lỗi', () => {
    toast('Lỗi A', 'error');
    toast('Đã lưu 1');
    toast('Đã lưu 1');
    assert.deepEqual(shown(), ['error:Lỗi A', 'success:Đã lưu 1']);

    for (let index = 2; index <= MAX_TOASTS + 1; index++) toast(`Đã lưu ${index}`);
    assert.equal(toastState.items.length, MAX_TOASTS);
    assert.equal(shown()[0], 'error:Lỗi A');
    assert.equal(shown().at(-1), `success:Đã lưu ${MAX_TOASTS + 1}`);
    assert.ok(!shown().includes('success:Đã lưu 1'));

    toastState.items = [];
    for (let index = 1; index <= MAX_TOASTS + 1; index++) toast(`Lỗi ${index}`, 'error');
    assert.deepEqual(shown()[0], 'error:Lỗi 2');
});

test('toast đã đóng bị dọn khi có toast mới; chuỗi rỗng không hiện', () => {
    toast('Cũ');
    toastState.items[0].open = false;
    toast('');
    toast('Mới');
    assert.deepEqual(shown(), ['success:Mới']);
});

test('hợp đồng lỗi: lỗi chung (chuỗi hoặc mảng) thành toast, lỗi theo ô thì không; plugin đăng ký thêm khoá', () => {
    assert.deepEqual(generalErrorMessages({ name: 'Bắt buộc', business: 'Đơn đã huỷ', product: ['Thiếu giá', 'Thiếu ảnh'], lock_version: '' }), ['Đơn đã huỷ', 'Thiếu giá', 'Thiếu ảnh']);
    assert.deepEqual(generalErrorMessages({ booking: 'Hãng từ chối' }), []);
    registerGeneralErrorKeys('booking', 'booking');
    assert.deepEqual(generalErrorMessages({ booking: 'Hãng từ chối' }), ['Hãng từ chối']);
});

test('lỗi HTTP ngoài Inertia có thông điệp dễ hiểu', () => {
    assert.match(httpErrorMessage(419), /hết hạn/);
    assert.match(httpErrorMessage(0), /kết nối/);
    assert.match(httpErrorMessage(503), /Máy chủ gặp lỗi/);
    assert.match(httpErrorMessage(418), /418/);
});
