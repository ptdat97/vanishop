# 17 — Danh mục plugin nghiệp vụ

> Theo [ADR-0009](adr/0009-core-toi-gian-nghiep-vu-bang-plugin.md), core chỉ cung cấp nguyên liệu thương mại và điểm mở rộng. Các tính năng dưới đây được xây thành **plugin** trong `custom/plugin/<Tên>/` và dùng các điểm mở rộng liệt kê ở [10 §6](10-hook-va-plugin.md).
>
> Mỗi plugin khi bắt đầu phát triển sẽ có đặc tả riêng tại `custom/plugin/<Tên>/README.md`. Tài liệu này chỉ chốt **phạm vi** và **phụ thuộc vào core**. Các mô tả nghiệp vụ trong tài liệu 05–09 được đánh dấu *(plugin)* là yêu cầu đầu vào cho plugin tương ứng.

## 1. Core cung cấp sẵn (không cần plugin)

| Nhóm | Mặc định trong core |
|---|---|
| Thanh toán | **COD**, **chuyển khoản thủ công** (hiển thị số tài khoản, nhân viên xác nhận) |
| Vận chuyển | **Phí cố định / theo bảng** (vùng, ngưỡng miễn phí), **vận đơn nhập tay** (hãng + mã vận đơn) |
| Phân bổ kho | Chiến lược mặc định: location ưu tiên cao nhất đủ hàng |
| Tính tiền | Tạm tính, phí vận chuyển, tách VAT, làm tròn VNĐ |
| Thông báo | Email (SMTP) |
| Đăng nhập | Mật khẩu + OTP qua email; **khung** OTP qua SMS/ZNS (cần plugin gửi) |
| Đổi trả | Quy trình RMA cơ bản, hoàn tiền thủ công |
| Tìm kiếm | Laravel Scout (driver database; Meilisearch qua cấu hình) |
| CMS | Trang, menu, banner, page builder với block cơ bản |
| Tích hợp | Integration API, webhook subscription, outbox/inbox, khung connector |

## 2. Plugin chính thức

Ký hiệu phase: P1 = cần cho go-live brand đầu tiên, P2/P3 = sau đó.

### 2.1 Thanh toán (`kind: payment_gateway`)

| Plugin | Phạm vi | Điểm mở rộng dùng | Phase |
|---|---|---|---|
| `VietQr` | QR động NAPAS, tự xác nhận qua webhook ngân hàng/Casso/SePay | `PaymentGateway`, inbound webhook | P1 |
| `VnPay` | Thẻ nội địa/quốc tế, QR ngân hàng | `PaymentGateway` | P1 |
| `MoMo`, `ZaloPay`, `ShopeePay` | Ví điện tử, deeplink | `PaymentGateway` | P2 |
| `OnePay`, `Payoo` | Cổng thẻ khác | `PaymentGateway` | theo nhu cầu |
| `Bnpl` | Trả góp / mua trước trả sau (Kredivo, Fundiin…) | `PaymentGateway` | P3 |

### 2.2 Vận chuyển (`kind: shipping_carrier`)

| Plugin | Phạm vi | Điểm mở rộng dùng | Phase |
|---|---|---|---|
| `Ghn`, `Ghtk` | Báo phí, tạo/huỷ vận đơn, webhook trạng thái, map địa giới | `ShippingCarrier`, `integration_mappings` | P1 (1 hãng) |
| `ViettelPost`, `JtExpress`, `NinjaVan` | Như trên | `ShippingCarrier` | P2 |
| `Ahamove` | Giao nhanh nội thành | `ShippingCarrier` | P3 |
| `CodReconciliation` | Import bảng kê COD, khớp vận đơn, chênh lệch, đẩy kế toán | event `ShipmentStatusChanged`, `Payment` contract, Admin page | P2 |

### 2.3 Bán hàng & khách hàng

| Plugin | Phạm vi | Điểm mở rộng dùng | Phase |
|---|---|---|---|
| `Promotion` | Engine điều kiện → hành động, voucher, flash sale, chống chồng KM, ngân sách, KM cross-brand (yêu cầu: [07 §2](07-khach-hang-khuyen-mai-loyalty.md)) | `TotalsCalculator`, `CheckoutValidator`, event `OrderPlaced/Cancelled`, Admin pages | P1 (bản cơ bản) |
| `Loyalty` | Hạng, tích/đổi điểm toàn tập đoàn, ledger (yêu cầu: [07 §3](07-khach-hang-khuyen-mai-loyalty.md)) | `TotalsCalculator`, events đơn/đổi trả, `customer.profile_tabs`, Integration (đơn POS) | P3 |
| `CodRiskGuard` | Chống "bom hàng": giới hạn COD, blacklist SĐT, OTP đơn giá trị cao, xác nhận đơn tự động | `CheckoutValidator`, `PaymentGateway::isAvailable` filter | P2 |
| `AbandonedCart` | Nhắc giỏ bỏ quên (email/ZNS) | event `CartAbandoned`, `NotificationChannel` | P2 |
| `Wishlist` | Yêu thích, báo có hàng lại/giảm giá | Storefront routes, event `AvailabilityChanged`, `PriceChanged` | P2 |
| `SizeAdvisor` | Gợi ý size theo số đo, size profile | Storefront block, `customer.profile_tabs` | P3 |
| `ProductBundle` | Set/combo, tồn = min thành phần | `TotalsCalculator`, `InventoryReservation` contract | P3 |
| `SocialLogin` | Google, Apple, Zalo | `auth.providers` registry | P2 |

### 2.4 Omnichannel & cửa hàng

| Plugin | Phạm vi | Điểm mở rộng dùng | Phase |
|---|---|---|---|
| `StoreOmnichannel` | Tra tồn tại cửa hàng, BOPIS, ship-from-store, endless aisle, trả hàng online tại cửa hàng, Store App (yêu cầu: [05 §7](05-ton-kho-va-cua-hang.md)) | `FulfillmentMethod` (pickup), `SourcingStrategy`, location capabilities, Admin pages quyền `store_staff` | P2 |
| `AdvancedSourcing` | Chấm điểm location theo khoảng cách, chi phí, tải; tách kiện | `SourcingStrategy` | P3 |
| `ChannelAllocation` | Giới hạn % tồn bán theo kênh | filter `vani.inventory.ats` | P3 |

### 2.5 Hoá đơn & pháp lý

| Plugin | Phạm vi | Điểm mở rộng dùng | Phase |
|---|---|---|---|
| `EInvoice` | Luồng HĐĐT: nháp, phát hành, điều chỉnh/thay thế, thông tin công ty ở checkout (yêu cầu: [09 §5](09-dac-thu-viet-nam.md)); định nghĩa contract `EInvoiceProvider` cho plugin nhà cung cấp | events `OrderCompleted`, `ReturnResolved`, `checkout.fields` | P2 |
| `EInvoiceVnpt`, `EInvoiceViettel`, `EInvoiceMisa`… | Driver nhà cung cấp | `EInvoiceProvider` (của plugin `EInvoice`) | P2 |

### 2.6 Thông báo

| Plugin | Phạm vi | Điểm mở rộng dùng | Phase |
|---|---|---|---|
| `ZaloZns` | Tin giao dịch qua Zalo ZNS, OTP | `NotificationChannel`, `OtpSender` | P1 |
| `SmsBrandname` | SMS brandname (nhiều nhà cung cấp), OTP | `NotificationChannel`, `OtpSender` | P1 |
| `WebPush` | Thông báo đẩy trình duyệt | `NotificationChannel` | P3 |

### 2.7 Kênh bán & marketing

| Plugin | Phạm vi | Điểm mở rộng dùng | Phase |
|---|---|---|---|
| `Shopee`, `Lazada`, `TikTokShop` | Đẩy sản phẩm/tồn, kéo đơn về luồng fulfillment chung | `Connector`, Integration mapping, channel type `marketplace` | P3 |
| `FeedExport` | Feed Google Merchant / Meta / TikTok Catalog | scheduled task, catalog read contract | P2 |
| `TrackingPixels` | GA4, Meta Pixel, TikTok Pixel theo brand, server-side events | hook slot storefront, events đơn | P1 |

### 2.8 Tích hợp hệ thống

| Plugin | Phạm vi | Điểm mở rộng dùng | Phase |
|---|---|---|---|
| `Erp<Tên>` (MisaAmis, SapB1, Odoo…) | Khi ERP không tự gọi Integration API: connector đồng bộ mã hàng, tồn, thanh toán | `Connector`, `integration_ownerships` | khi chốt ERP |
| `Odo` | Tạm hoãn ([ADR-0007](adr/0007-integration-module-odo-deferred.md)) | `Connector` hoặc Integration Client | khi chốt ODO |
| `PosSync` | Đồng bộ đơn/khách từ POS hiện hữu | `Connector` / Integration API `pos-orders` | P3 |

### 2.9 Báo cáo & nội dung

| Plugin | Phạm vi | Phase |
|---|---|---|
| `AdvancedReports` | Báo cáo hợp nhất Owner, cohort, RFM, phân bổ chi phí KM/loyalty liên brand | P3 |
| `Blog`, `Lookbook` | Nội dung, block storefront bổ sung | P2 |

## 3. Quy tắc cho plugin chính thức

- Tuân thủ [10 §5](10-hook-va-plugin.md) và checklist clean-room ([01](01-clean-room-va-license.md)).
- Có test Pest riêng, chạy trong CI chung; **không** merge nếu làm đỏ test core.
- Plugin phụ thuộc plugin khác thì khai báo trong `requires.plugins` (ví dụ `EInvoiceMisa` cần `EInvoice`).
- Nếu plugin cần một điểm mở rộng mà core chưa có, **mở PR bổ sung điểm mở rộng vào core** (kèm tài liệu ở [10 §6](10-hook-va-plugin.md)), không vá core từ trong plugin.
