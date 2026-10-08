// Load test storefront theo NFR (docs/02-architecture/overview.md §7) — chạy trên STAGING, không chạy vào production.
//
//   k6 run -e BASE=https://staging.shop.vn scripts/load/storefront.js
//   k6 run -e BASE=... -e ORDERS_PER_MIN=500 -e PAGE_RPS=200 -e DURATION=10m scripts/load/storefront.js
//
// Điều kiện: IP máy chạy k6 nằm trong VANI_LOAD_TEST_IPS của staging (bỏ qua rate limit storefront — nếu không sẽ
// nhận 429 từ request thứ 21/phút của checkout); có cổng COD bật; catalog có hàng tồn đủ lớn (đơn COD giữ hàng thật).
// Sau khi chạy: kiểm tra 0 oversell (tồn không âm, đối soát tồn kho) — xem overview §7.
import http from 'k6/http';
import { check, fail } from 'k6';
import { randomItem } from 'https://jslib.k6.io/k6-utils/1.4.0/index.js';

const BASE = (__ENV.BASE || 'http://vanishop.test').replace(/\/$/, '');
const API = `${BASE}/api/storefront/v1`;
const DURATION = __ENV.DURATION || '5m';
const JSON_HEADERS = { 'Content-Type': 'application/json', Accept: 'application/json' };

export const options = {
    scenarios: {
        // Khách xem trang (HTML có page cache + gọi /_vani/phien như trình duyệt).
        browse: { executor: 'constant-arrival-rate', exec: 'browse', rate: Number(__ENV.PAGE_RPS || 50), timeUnit: '1s', duration: DURATION, preAllocatedVUs: 50, maxVUs: 500 },
        // Ứng dụng/headless gọi Storefront API.
        api: { executor: 'constant-arrival-rate', exec: 'api', rate: Number(__ENV.API_RPS || 30), timeUnit: '1s', duration: DURATION, preAllocatedVUs: 30, maxVUs: 300 },
        // Đặt đơn COD: tạo giỏ → thêm hàng → báo giá → đặt đơn. NFR: 500 đơn/phút giờ cao điểm.
        checkout: { executor: 'constant-arrival-rate', exec: 'checkout', rate: Number(__ENV.ORDERS_PER_MIN || 60), timeUnit: '1m', duration: DURATION, preAllocatedVUs: 20, maxVUs: 400 },
    },
    thresholds: {
        'http_req_duration{kind:page}': ['p(95)<300'],
        'http_req_duration{kind:api}': ['p(95)<200'],
        'http_req_duration{kind:checkout}': ['p(95)<1000'],
        'http_req_failed': ['rate<0.01'],
        'checks': ['rate>0.99'],
    },
};

export function setup() {
    const products = http.get(`${API}/products?per_page=50`, { headers: JSON_HEADERS }).json('data') || [];
    const categories = (http.get(`${API}/categories`, { headers: JSON_HEADERS }).json('data') || []).map((category) => category.slug);
    const slugs = products.map((product) => product.slug);
    const variants = [];
    for (const slug of slugs.slice(0, 20)) {
        const detail = http.get(`${API}/products/${slug}`, { headers: JSON_HEADERS }).json('data');
        (detail?.variants || []).filter((variant) => variant.available).forEach((variant) => variants.push(variant.id));
    }
    if (slugs.length === 0 || variants.length === 0) {
        fail('Không có sản phẩm/biến thể còn hàng — seed dữ liệu staging trước.');
    }

    return { slugs, categories, variants };
}

export function browse(data) {
    const pages = ['/', `/san-pham/${randomItem(data.slugs)}`];
    if (data.categories.length > 0) {
        pages.push(`/danh-muc/${randomItem(data.categories)}`);
    }
    const response = http.get(`${BASE}${randomItem(pages)}`, { tags: { kind: 'page', name: 'page' } });
    check(response, { 'trang 200': (r) => r.status === 200 });
    http.get(`${BASE}/_vani/phien`, { tags: { kind: 'api', name: 'session' } });
}

export function api(data) {
    const response = Math.random() < 0.5
        ? http.get(`${API}/products?per_page=24`, { headers: JSON_HEADERS, tags: { kind: 'api', name: 'products' } })
        : http.get(`${API}/products/${randomItem(data.slugs)}`, { headers: JSON_HEADERS, tags: { kind: 'api', name: 'product' } });
    check(response, { 'api 200': (r) => r.status === 200 });
}

export function checkout(data) {
    const tags = { kind: 'checkout' };
    const created = http.post(`${API}/carts`, null, { headers: JSON_HEADERS, tags: { ...tags, name: 'cart' } });
    if (!check(created, { 'tạo giỏ 201': (r) => r.status === 201 })) {
        return;
    }
    const cart = created.json('data.id');
    const headers = { ...JSON_HEADERS, 'X-Vani-Cart-Token': created.json('meta.token') };
    const line = http.post(`${API}/carts/${cart}/lines`, JSON.stringify({ variant_id: randomItem(data.variants), quantity: 1 }), { headers, tags: { ...tags, name: 'line' } });
    if (!check(line, { 'thêm hàng 200': (r) => r.status === 200 })) {
        return; // hết hàng: không tính là lỗi hệ thống nhưng vẫn ghi nhận ở checks
    }
    const order = {
        contact: { full_name: 'Khách Load Test', phone: `09${String(Math.floor(Math.random() * 1e8)).padStart(8, '0')}`, email: `load-${__VU}-${__ITER}@example.test` },
        shipping_address: { province_code: '29', province_name: 'Thành phố Hồ Chí Minh', ward_code: '70101065', ward_name: 'Phường Bến Thành', street_line: '12 Lê Lợi' },
        shipping_method: 'standard',
        payment_method: 'cod',
        voucher_codes: [],
    };
    const quote = http.post(`${API}/checkout/${cart}/quote`, JSON.stringify(order), { headers, tags: { ...tags, name: 'quote' } });
    if (!check(quote, { 'báo giá 200': (r) => r.status === 200 })) {
        return;
    }
    const placed = http.post(`${API}/checkout/${cart}/orders`, JSON.stringify({ ...order, expected_total: quote.json('data.total.amount') }), {
        headers: { ...headers, 'Idempotency-Key': `load-${__VU}-${__ITER}-${Date.now()}` },
        tags: { ...tags, name: 'order' },
    });
    check(placed, { 'đặt đơn 201': (r) => r.status === 201 });
}
