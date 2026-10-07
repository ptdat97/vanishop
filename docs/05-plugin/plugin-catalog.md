# Danh mục plugin

> Trạng thái: ba plugin chứng minh kiến trúc đã **cài đặt trong repo** (slice 10 — xem [roadmap](../20-roadmap/roadmap.md)); các plugin còn lại ở mức **Planned** ([status](../00-overview/status.md)). Tài liệu này chốt **phạm vi** và **extension point** mỗi plugin dùng; đặc tả chi tiết nằm trong `custom/plugin/<Name>/README.md`.

## 1. Core có sẵn và plugin hệ thống

Theo [ADR-029](../19-adr/ADR-029-commerce-microkernel.md), Core chỉ giữ mặc định **trung lập thị trường**; mặc định mang chính sách kinh doanh/đặc thù VN là **plugin hệ thống** (đóng gói sẵn, tự bật). Đã tách khỏi module ở slice 12d (2026-10-02); `php artisan vani:install` cài + bật ([commerce-kernel §6](../02-architecture/commerce-kernel.md)).

| Nhóm | Mặc định trong Core (trung lập) | Plugin hệ thống (bundled) |
|---|---|---|
| Thanh toán | — | `vani.cod`, `vani.bank-transfer` |
| Vận chuyển | Vận đơn nhập tay (`manual`) | `vani.shipping-flat-rate` (phí cố định + ngưỡng miễn phí) |
| Thuế | `none` (dự phòng) | `vani.tax-vn-vat` (VAT giá đã gồm thuế) |
| Địa chỉ | Nhập tự do | `vani.provinces-vn` (34 tỉnh/thành, 3.321 phường/xã — 07/2025) |
| Phân bổ kho | `reserved_locations` | |
| Khuyến mãi | Engine + action `percent_off`/`amount_off` + voucher. **Không có rule điều kiện** trong Core — rule đến từ plugin (`vani.promotion-rules`) | |
| Đổi trả | `days_window` | |
| Thông báo | Email (`mail`) | |
| Đăng nhập | Mật khẩu, OTP email |
| Tìm kiếm | Database (Meilisearch: plugin `vani.search-meilisearch`) |
| Tích hợp | Integration API, webhook, outbox/inbox, khung connector |

## 2. Ba plugin chứng minh kiến trúc (đã có trong repo)

Mục tiêu: chứng minh **thêm capability thật mà không sửa Commerce Core** ([roadmap](../20-roadmap/roadmap.md)).

| Plugin | Thư mục | Contract | Chứng minh được |
|---|---|---|---|
| `vani.promotion-rules` | `custom/plugin/PromotionRules` | `PromotionRule` (tag `PromotionRule::TAG`) | Rule nghiệp vụ cắm vào engine của Core: `min_order_subtotal`, `min_quantity`, `in_collections` (qua `CollectionDirectory`), `first_order_only` (qua `OrderReader`) |
| `vani.vietqr` | `custom/plugin/VietQr` | `PaymentGateway` (tag `PaymentGateway::TAG`) | Cổng QR động (payload QR theo số tiền/số đơn), IPN HMAC qua route chung `/api/payments/vietqr/callback`, hoàn tiền idempotent, cấu hình tài khoản theo pháp nhân |
| `vani.ghn` | `custom/plugin/Ghn` | `ShippingCarrier` + `ShippingRateProvider` | Báo cước ở checkout, đặt vận đơn idempotent sau commit, webhook trạng thái map về `ShipmentStatus` chuẩn. **Bản mô phỏng**: phí từ cấu hình, mã vận đơn sinh trong bộ nhớ, chưa gọi API GHN ([shipping-carrier](contracts/shipping-carrier.md)) |
| `vani.sms-brandname` | `custom/plugin/SmsBrandname` | `NotificationChannel` (`sms`) + `OtpSender` | SMS brandname qua eSMS: brandname theo brand, nội dung bỏ dấu, `RequestId` idempotent, phân loại lỗi thử lại/vĩnh viễn |
| `vani.zalo-zns` | `custom/plugin/ZaloZns` | `NotificationChannel` (`zns`) + `OtpSender` | ZNS theo template đã duyệt (tham số render từ biến), tự làm mới access token (refresh token xoay vòng), OTP ưu tiên ZNS rồi dự phòng SMS |

Cả ba đều chạy bộ contract test của Core (`PaymentGatewayContract`, `ShippingCarrierContract`) và chỉ đóng góp implementation cho extension point khi plugin được bật.

## 3. Danh mục theo nhóm

Đợt: **P1** = cần để go-live cửa hàng · **P2** · **P3** · **Later**.
Cột Đợt ghi ✅ nghĩa là plugin đã có trong `custom/plugin/`.

### Thanh toán
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.vietqr` ✅ | `PaymentGateway` | P1 |
| `vani.vnpay` | `PaymentGateway`, `CallbackResponder` | **Implemented** (Core 0.3.15); chờ chạy thử với TMN sandbox thật |
| `vani.momo`, `vani.zalopay`, `vani.shopeepay` | `PaymentGateway` | P2 |
| `vani.bnpl` (Kredivo, Fundiin…) | `PaymentGateway` | P3 |

### Vận chuyển
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.ghn` ✅ (thay cho `vani.ghtk`) | `ShippingCarrier` | P1 |
| `vani.viettelpost`, `vani.jt`, `vani.ninjavan` | `ShippingCarrier` | P2 |
| `vani.ahamove` | `ShippingCarrier` | P3 |
| `vani.cod-reconciliation` | Events fulfillment, `PaymentRecorder`, Admin pages | P2 |

### Bán hàng, khuyến mãi, khách hàng
| Plugin | Contract / điểm mở rộng | Đợt |
|---|---|---|
| `vani.promotion-rules` ✅ | `PromotionRule`, `PromotionAction` | P1 |
| `vani.promotion-advanced` (BxGy, combo, quà tặng, flash sale, mua kèm khác brand) | `PromotionRule`, `PromotionAction` | P3 |
| `vani.cod-risk-guard` | `CheckoutValidator`, filter `vani.checkout.payment_methods` | P2 |
| `vani.abandoned-cart` | Event `CartAbandoned`, `NotificationChannel` | P2 |
| `vani.wishlist` | Storefront route, events `AvailabilityChanged`, `PriceChanged` | P2 |
| `vani.social-login` | Auth provider registry | P2 |
| `vani.loyalty` ([spec](specs/loyalty.md)) | `TotalsCalculator`, events đơn | P3 |
| `vani.size-advisor` | `StorefrontBlock`, `customerProfileTabs()` | P3 |
| `vani.product-bundle` | `TotalsCalculator`, `InventoryReservation` | P3 |

### Omnichannel & tồn kho
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.store-omnichannel` ([spec](specs/store-omnichannel.md)) | `FulfillmentMethod`, `SourcingStrategy` | P2 |
| `vani.advanced-sourcing` | `SourcingStrategy` | P3 |
| `vani.marketplace-allocation` (chừa tồn cho sàn TMĐT) | `InventoryStrategy` | P3 |

### Hoá đơn & thông báo
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.einvoice` (định nghĩa `EInvoiceProvider` cho plugin nhà cung cấp) | Events `OrderCompleted`, `ReturnResolved`; `checkoutFields()` | P2 |
| `vani.einvoice-vnpt`, `-viettel`, `-misa`… | `EInvoiceProvider` (của `vani.einvoice`) | P2 |
| `vani.zalo-zns` ✅, `vani.sms-brandname` ✅ | `NotificationChannel`, `OtpSender` | P1 |
| `vani.webpush` | `NotificationChannel` | P3 |

### Sàn TMĐT, marketing, tìm kiếm
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.tracking-pixels` (GA4, Meta, TikTok) | Slot storefront, events đơn | P1 |
| `vani.feed-export` (Google Merchant, Meta, TikTok catalog) | `CatalogReader`, scheduled task | P2 |
| `vani.shopee`, `vani.lazada`, `vani.tiktokshop` | `Connector`, đơn kéo về có `source = marketplace` | P3 |
| `vani.search-meilisearch` ✅ | `SearchProvider` + `ConfigurableSearchIndex` | P1 (tách từ Core 2026-10-14) |
| `vani.search-algolia` / `vani.search-elastic` | `SearchProvider` | Later |
| `vani.recommendation` | Hook listing, `StorefrontBlock` | Later |

### Tích hợp hệ thống
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.erp-<tên>` (Odoo, SAP B1, MISA AMIS…) | `ErpConnector` ([erp-integration](../11-integration/erp-integration.md)) | Khi chốt ERP |
| `vani.odo` | `Connector` hoặc Integration Client | Khi chốt vai trò ODO |
| `vani.pos-sync` | `Connector` / Integration API | P3 |

### Mô hình kinh doanh mới
| Plugin | Tài liệu | Đợt |
|---|---|---|
| `vani.marketplace` | [marketplace](../13-marketplace/marketplace.md) | Later |
| `vani.creator` (creator/affiliate/attribution) | [creator-affiliate](../13-marketplace/creator-affiliate.md) | Later |

### Báo cáo & nội dung
| Plugin | Đợt |
|---|---|
| `vani.reports` (widget Tổng quan, báo cáo doanh thu theo ngày/sản phẩm/thương hiệu/thanh toán/kênh, CSV) | **Implemented** (Core 0.3.12) |
| `vani.advanced-reports` (hợp nhất Owner, cohort, RFM, phân bổ chi phí KM/loyalty) | P3 |
| `vani.cms` (trang `/trang/*`, tin tức `/tin-tuc`, Markdown, hẹn giờ, xem trước, SEO, link header/footer, khối "Bài viết mới", sitemap, Storefront API) | **Implemented** (Core 0.3.14) — thay cho `vani.blog`; URL cố định; WYSIWYG hoãn |
| `vani.lookbook` | P2 |
| `vani.demo-catalog` (dữ liệu demo: sản phẩm có ảnh thật từ VaniCommerce, tham chiếu CatalogImporter/PriceImporter/StockImporter) | **Implemented** (Core 0.3.27) |

## 4. Quy tắc

Tuân thủ [plugin-system §10](plugin-system.md). Plugin phụ thuộc plugin khác thì khai báo `requires.plugins` (ví dụ `vani.einvoice-misa` → `vani.einvoice`).
