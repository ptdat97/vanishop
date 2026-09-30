# Danh mục plugin nghiệp vụ

> Trạng thái: ba plugin chứng minh kiến trúc đã **cài đặt trong repo** (slice 10 — xem [roadmap](../20-roadmap/roadmap.md)); các plugin còn lại ở mức **Planned** ([status](../00-overview/status.md)). Tài liệu này chốt **phạm vi** và **extension point** mỗi plugin dùng; đặc tả chi tiết nằm trong `custom/plugin/<Name>/README.md`.

## 1. Core có sẵn (không cần plugin)

| Nhóm | Mặc định trong Core |
|---|---|
| Thanh toán | COD, chuyển khoản thủ công |
| Vận chuyển | Phí cố định/theo bảng (`flat_rate`), vận đơn nhập tay (`manual`) |
| Phân bổ kho | `priority_first_fit` |
| Khuyến mãi | Framework + action `percent_off`/`amount_off` + voucher. **Chưa có rule điều kiện nào** trong Core — rule điều kiện đến từ plugin (`vani.promotion-rules`) |
| Thuế | VAT giá đã gồm thuế |
| Thông báo | Email |
| Đăng nhập | Mật khẩu, OTP email |
| Tìm kiếm | Database, Meilisearch |
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

Cả ba đều chạy bộ contract test của Core (`PaymentGatewayContract`, `ShippingCarrierContract`) và chỉ đóng góp implementation cho extension point trong phạm vi (`owner`/`brand`) mà plugin được bật.

## 3. Danh mục theo nhóm

Đợt: **P1** = cần để go-live brand đầu tiên · **P2** · **P3** · **Later**.
Cột Đợt ghi ✅ nghĩa là plugin đã có trong `custom/plugin/`.

### Thanh toán
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.vietqr` ✅ | `PaymentGateway` | P1 |
| `vani.vnpay` | `PaymentGateway` | P1 |
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
| `vani.promotion-advanced` (BxGy, combo, quà tặng, flash sale, cross-brand) | `PromotionRule`, `PromotionAction` | P3 |
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
| `vani.channel-allocation` | `InventoryStrategy` | P3 |

### Hoá đơn & thông báo
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.einvoice` (định nghĩa `EInvoiceProvider` cho plugin nhà cung cấp) | Events `OrderCompleted`, `ReturnResolved`; `checkoutFields()` | P2 |
| `vani.einvoice-vnpt`, `-viettel`, `-misa`… | `EInvoiceProvider` (của `vani.einvoice`) | P2 |
| `vani.zalo-zns` ✅, `vani.sms-brandname` ✅ | `NotificationChannel`, `OtpSender` | P1 |
| `vani.webpush` | `NotificationChannel` | P3 |

### Kênh bán, marketing, tìm kiếm
| Plugin | Contract | Đợt |
|---|---|---|
| `vani.tracking-pixels` (GA4, Meta, TikTok) | Slot storefront, events đơn | P1 |
| `vani.feed-export` (Google Merchant, Meta, TikTok catalog) | `CatalogReader`, scheduled task | P2 |
| `vani.shopee`, `vani.lazada`, `vani.tiktokshop` | `Connector`, channel type `marketplace` | P3 |
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
| `vani.advanced-reports` (hợp nhất Owner, cohort, RFM, phân bổ chi phí KM/loyalty) | P3 |
| `vani.blog`, `vani.lookbook` | P2 |

## 4. Quy tắc

Tuân thủ [plugin-system §10](plugin-system.md). Plugin phụ thuộc plugin khác thì khai báo `requires.plugins` (ví dụ `vani.einvoice-misa` → `vani.einvoice`).
