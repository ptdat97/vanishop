# vani.provinces-vn

Plugin hệ thống (`"bundled": true`): danh mục địa giới hành chính Việt Nam **2 cấp** (tỉnh/thành – phường/xã) sau sắp xếp 07/2025 — 34 tỉnh/thành, 3.321 phường/xã — cung cấp `Modules\Checkout\Contracts\AddressDirectory`.

- Checkout (API + native): `shipping_address.province_code` + `ward_code` phải hợp lệ và khớp nhau; tên lưu vào đơn lấy theo danh mục.
- Storefront API: `GET /api/storefront/v1/address/provinces`, `GET /api/storefront/v1/address/provinces/{code}/wards`.
- Dữ liệu: `Database/data/vn-divisions-2025.json` (mã tỉnh theo danh mục Bộ Nội vụ; `code_tms` giữ để đối chiếu). Cập nhật danh mục = thay file + tăng version plugin.
- Tắt plugin → địa chỉ nhập tự do.
