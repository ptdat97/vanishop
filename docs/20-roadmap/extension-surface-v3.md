# Điểm mở rộng v3 — theo bài toán vận hành thật (EP v3)

> Trạng thái: **Planned** (Owner giao 2026-11-15). Tiếp nối [extension-surface-v2](../04-extension/extension-surface-v2.md) (W1–W6 đã xong: storefront, form/cột/lọc Admin cho sản phẩm–đơn–khách, giỏ, thanh toán giữ tiền, danh tính, khối trang chủ, báo cáo). Danh mục hiện có: [extension-point-catalog](../04-extension/extension-point-catalog.md). Ràng buộc: [ADR-029](../19-adr/ADR-029-commerce-microkernel.md) (microkernel), [ADR-046](../19-adr/ADR-046-staffed-operations-vietnam.md) (ba chốt có người, tự động là điểm mở rộng), [ADR-027](../19-adr/ADR-027-plugin-data-no-core-columns.md) (plugin không thêm cột Core), R26 (mỗi điểm mở rộng public có implementation tham chiếu + contract test).

## 1. Mục tiêu

Một shop thời trang SME ở Việt Nam muốn **tuỳ biến cách vận hành** — chấm điểm bom hàng, chọn hãng theo tuyến, in phiếu theo mẫu riêng, tính giá sale theo giá vốn, chuyển trả tiền qua ngân hàng — mà **không sửa Core**. Hiện storefront và luồng đặt hàng đã mở rộng tốt; phía **vận hành Admin** (ba chốt ADR-046, vận đơn, đổi trả, thanh toán, tồn, bảng giá) phần lớn còn đóng, và plugin **không gọi được các lệnh của nhân viên** (xác nhận/huỷ có chính sách, ghi chú, thu COD) qua contract public.

Kế hoạch dưới gom khoảng trống theo **bài toán**, không theo module. Với mỗi bài toán: điểm mở rộng cần thêm, plugin tham chiếu (thoả R26), bất biến giữ nguyên.

## 2. Hiện trạng tóm tắt

| Bề mặt | Đã mở | Còn đóng (khoảng trống) |
|---|---|---|
| Storefront | ~60 slot, `StorefrontEnricher`, `StorefrontBlock`, trang/route plugin, `LinkType`, `SitemapProvider` | — (đủ cho P1–P2) |
| Luồng giao dịch | `CartLineOption`, `vani.cart.validate_line`, `CheckoutValidator`, `TotalsCalculator`, hook `vani.service.checkout.totals`/`pricing.prices`/`promotion.result`, `PromotionRule`/`Action`, `PaymentGateway`, `ShippingRateProvider`, `TaxCalculator` | Phụ thu theo tuỳ chọn dòng (đã ghi ở catalog §7) |
| Admin — sản phẩm, đơn, khách, thương hiệu, danh mục | `adminColumn`/`adminFilter`/`adminAction`/`adminTab`/`adminFormSection`, slot `vani.admin.order.*`, `vani.admin.product.*`, `vani.admin.dashboard.tasks` | — |
| Admin — **vận đơn, đổi trả, thanh toán, tồn kho, bảng giá, khuyến mãi** | Không có (trừ `StockPageLink`) | Cột, lọc, thao tác hàng loạt, tab chi tiết, công cụ tải file |
| Ba chốt ADR-046 | Tự động hoá: `vani.cod` `auto_confirm_mode`, `ShipmentBooking::pushToCarrier`, `ReturnDecision::$autoApprove`; sự kiện `OrderPlaced`/`ShipmentCreated`/`ReturnRequested` | **Chặn** chốt khi chưa đủ điều kiện (guard); **tín hiệu** cho nhân viên quyết định (rủi ro, gợi ý); **lệnh** nhân viên cho plugin gọi |
| Lệnh nghiệp vụ cho plugin | `OrderTransitions` (thô, không chính sách), `Payments` (thiếu thu COD), `Returns`, `ShipmentBooking`, `PriceImporter`, `StockImporter` | Xác nhận/huỷ đơn **qua chính sách** + audit, ghi chú đơn, sự cố giao hàng, thu COD, gắn nhãn/nhóm khách |

## 3. Bài toán → điểm mở rộng

### X1 — Màn vận hành mở cho plugin (nền cho mọi đợt sau)

**Bài toán thật:** shop muốn thêm cột "Bưu cục nhận" ở danh sách vận đơn (GHN), lọc đổi trả theo "lý do nhân viên ghi", nút "Đối chiếu sao kê" ở trang thanh toán, cột "Hàng đang về" ở lưới tồn, cột "Giá vốn/biên lợi nhuận" ở trang làm việc bảng giá.

| Điểm mở rộng | Loại | Ghi chú |
|---|---|---|
| `adminColumn` / `adminFilter` / `adminAction` (đơn lẻ + hàng loạt) cho resource `shipment`, `return`, `payment`, `stock` (theo SKU), `price_list_item` (SKU trong bảng giá), `promotion` | Registry (mở rộng W2) | Dùng lại `AdminScreen` đang phục vụ `product`/`order`/`customer`; Core kiểm quyền + audit thao tác như hiện nay |
| `adminTab` cho `return`, `shipment` (trang chi tiết) | Registry | Ví dụ: tab "Ảnh kiểm hàng" của plugin chụp ảnh khi nhận hàng trả |
| Slot `vani.admin.<resource>.list_tools` cho `shipment`, `payment`, `stock`, `price_list` | Slot | Công cụ tải file (nhập sao kê, nhập tồn từ hãng) — cùng hợp đồng với `vani.admin.order.list_tools` |
| `SkuPicker` nhận bộ lọc phụ do plugin khai báo (`vani.admin.sku_picker.filters`) | Slot (Admin) | Lọc theo bộ sưu tập/nhãn của plugin khi chọn SKU số lượng lớn |

**Tham chiếu:** `vani.hello-world` thêm một cột + một thao tác trên mỗi resource mới (contract test chung `AdminScreenContract`). **Bất biến:** plugin không sửa cột/thao tác của Core; thao tác plugin chạy qua kiểm quyền + audit của Core.

### X2 — Ba chốt có người: chặn, tín hiệu, lệnh (ADR-046)

**Bài toán thật:**
- *Chống bom hàng*: chấm điểm theo SĐT/địa chỉ/lịch sử (dịch vụ ngoài như dữ liệu bom hàng dùng chung), hiện điểm ngay trên dòng "Cần xác nhận"; điểm cao thì **không cho xác nhận** khi chưa gọi khách.
- *Gọi/nhắn xác nhận*: tổng đài (Stringee) hoặc Zalo OA gửi tin "Xác nhận đơn?", khách bấm "Đồng ý" → đơn được xác nhận với lịch sử ghi rõ kênh.
- *Đẩy lên hãng*: hãng từ chối đơn thiếu khối lượng/sai phường → chặn đẩy và báo trước, đỡ một vòng lỗi `booking_failed`.
- *Duyệt đổi trả*: không cho duyệt khi khách chưa gửi ảnh (quy định riêng của shop).

| Điểm mở rộng | Loại | Hợp đồng (phác thảo) |
|---|---|---|
| `OrderRiskSignal` | Contract (tag `vani.ordering.risk_signals`) | `assess(list<int> $orderIds): array<int, list<RiskSignal{level: low\|medium\|high, label, reason, source}>>` theo lô; Core gộp với nhãn lịch sử SĐT hiện có, hiện ở dòng "Cần xác nhận", trang đơn, và đưa vào `AutoConfirmCodOrder` (chế độ `trusted` bỏ qua đơn có tín hiệu `high`). Lỗi plugin → bỏ tín hiệu, không chặn trang |
| Hook guard `vani.order.before_confirm`, `vani.fulfillment.before_push`, `vani.returns.before_approve`, `vani.payment.before_refund` | Hook validate | Chạy trong lệnh của nhân viên **và** của tự động hoá (cùng một lệnh); trả `list<string>` lý do chặn → lỗi `business`, nhân viên thấy lý do; hàng loạt: bỏ qua dòng bị chặn kèm lý do (như các thao tác hàng loạt hiện có) |
| `OrderActions` | Service contract | `confirm(orderId, reason, source)`, `cancel(orderId, reason, source)` theo `OrderPolicy`, `addNote(orderId, text, source)`, `setDeliveryIssue(...)` — thay cho việc plugin gọi `OrderTransitions` thô (không chính sách, không audit). Nguồn `system:<plugin>` vào `order_events` |
| `ConfirmationChannel` | Contract (tag `vani.ordering.confirmation_channels`) | Gửi yêu cầu xác nhận cho khách (`request(orderId)`), nhận phản hồi qua webhook của plugin rồi gọi `OrderActions::confirm`/`cancel`. Core hiện nút "Gửi xác nhận qua …" trên dòng "Cần xác nhận" và trạng thái "đã gửi, chờ khách" |

**Tham chiếu:** `vani.cod-risk-guard` (đã có trong plugin-catalog, P2) cho `OrderRiskSignal` + `before_confirm`; `vani.zalo-zns` cho `ConfirmationChannel`; fixture trong test cho guard của vận đơn/đổi trả/hoàn tiền. **Bất biến:** guard chỉ **chặn**, không đổi dữ liệu; không có đường tắt cho tự động hoá (cùng lệnh, cùng guard — xem test kiến trúc `StaffedCheckpointsTest`).

### X3 — Giao hàng theo tuyến và in phiếu theo mẫu

**Bài toán thật:**
- Nội thành HCM giao Ahamove trong ngày, ngoại tỉnh GHN, COD trên 3 triệu đi GHTK (bảo hiểm hàng); hiện vận đơn lấy hãng theo phương thức khách chọn hoặc hãng mặc định.
- Phiếu giao theo mẫu của shop (logo, QR Zalo OA, lời cảm ơn, ghi chú "cho xem hàng"); phiếu A6 cho máy in nhiệt.
- Khách đổi trả: hãng đến lấy hàng tận nơi (đơn hoàn ngược).

| Điểm mở rộng | Loại | Hợp đồng (phác thảo) |
|---|---|---|
| `CarrierAssignment` | Contract (tag `vani.fulfillment.carrier_assignment`, chọn theo cấu hình) | `assign(ShipmentDraft): ?CarrierChoice{carrierCode, serviceCode, reason}` khi tạo vận đơn; null = theo mặc định hiện nay. Nhân viên vẫn đổi được trước khi đẩy |
| `PackingSlipTemplate` | Contract + override view | Mẫu phiếu giao (khổ A4/A5/A6, khối nội dung) ngoài mẫu Core; nhân viên chọn mẫu khi in; dữ liệu là `OrderDetail` + `ShipmentView` (không truy vấn riêng) |
| `ReturnPickup` | Interface tuỳ chọn của `ShippingCarrier` | `bookReturn(ReturnPickupData): CarrierShipment` — nút "Gọi hãng lấy hàng trả" trên yêu cầu đổi trả đã duyệt; trạng thái từ webhook hãng về `ReturnStatus::InTransit/Received` qua lệnh của `Returns` |

**Tham chiếu:** `vani.ghn` (thêm `ReturnPickup`), plugin mẫu `vani.carrier-routing` (bảng quy tắc tỉnh/COD → hãng), mẫu phiếu A6 trong `vani.hello-world`. **Bất biến:** COD trên vận đơn do Core tính, không plugin nào sửa (`vani.service.fulfillment.shipment_data` đã cấm đổi `codAmount`).

### X4 — Giá và bảng giá theo cách làm của shop

**Bài toán thật:**
- Giá sale = giá vốn (từ ERP/file) × 1,3, làm tròn lên 9.000 (199.000, 299.000) — "giá đẹp" kiểu bán lẻ.
- Cảnh báo bán dưới giá vốn hoặc dưới giá tối thiểu thương hiệu (MAP) khi áp giá hàng loạt — vẫn **không chặn** (Owner: giá sale có thể về 0đ).
- Giá theo số lượng (sỉ: từ 5 sản phẩm giảm thêm) — `prices.min_qty` đã có trong schema nhưng chưa dùng.

| Điểm mở rộng | Loại | Hợp đồng (phác thảo) |
|---|---|---|
| `BulkPriceMode` | Contract (tag `vani.pricing.bulk_modes`) | Thêm cách tính vào hộp thoại "Áp giá…" ngoài giảm %/số tiền/đồng giá: `code()`, `label()`, `fields()` (`FieldDefinition`), `compute(list<int> variantIds, array input): array<int, int\|null>`. Core lo xem trước, cảnh báo, ghi `setPrices` |
| `PriceRounding` | Contract (tag `vani.pricing.roundings`) | Cách làm tròn ngoài "1.000đ": "đuôi 9.000", "đuôi 900"… dùng chung cho áp giá hàng loạt và `BulkBasePrice` |
| Filter `vani.pricing.bulk.warnings` | Hook filter | Thêm cảnh báo theo dòng (dưới giá vốn, dưới MAP) vào xem trước và trang làm việc; chỉ **thêm** cảnh báo, không bỏ cảnh báo Core |
| Giá theo số lượng | Core + `PricingStrategy` | Mở `min_qty` trên trang làm việc bảng giá; `PricingContext` mang số lượng dòng giỏ. Cần ADR riêng (ảnh hưởng giá dòng giỏ/snapshot) |

**Tham chiếu:** `vani.cost-price` (giá vốn nhập từ file/ERP, cột biên lợi nhuận ở X1, mode "theo giá vốn", cảnh báo dưới giá vốn). **Bất biến:** tiền chỉ đổi qua `setPrices`/`vani.service.pricing.prices`; lịch sử giá luôn ghi.

### X5 — Tiền: đối soát và chuyển trả

**Bài toán thật:**
- Chuyển khoản: tự khớp sao kê ngân hàng (Casso, SePay, webhook ngân hàng) với số đơn trong nội dung CK → xác nhận đã nhận tiền, nhân viên chỉ xử lý giao dịch lệch.
- COD: hãng chuyển tiền theo bảng kê; đối soát từng vận đơn, đánh dấu chênh lệch/phí.
- Hoàn tiền COD/chuyển khoản: chuyển trả qua API ngân hàng/ví thay vì CSKH tự chuyển rồi bấm "Đã chuyển trả".

| Điểm mở rộng | Loại | Hợp đồng (phác thảo) |
|---|---|---|
| `Payments` thêm `collectCod(orderId, amount, reference)`, `collectCodManually(paymentId, note)`, `confirmManually(paymentId, note, source)` | Service contract (mở rộng) | Hiện chỉ có trong `PaymentService` (internal) — plugin đối soát không gọi được |
| `RefundPayout` | Contract (tag `vani.payment.refund_payouts`) | `payout(RefundData): PayoutResult{ok, reference, message}` cho khoản hoàn ở trạng thái "chờ chuyển trả"; Core hiện nút "Chuyển trả qua …", thành công → `completeManualRefund` với tham chiếu; lỗi → giữ "chờ chuyển trả". Chạy ngoài transaction (`ExternalCalls::guard`) |
| Bảng kê đối soát chung | Service contract `Reconciliation` | Plugin ghi dòng đối soát (nguồn, tham chiếu, số tiền, trạng thái khớp/lệch) vào bảng chung; Admin có trang "Đối soát" với hàng đợi "Lệch" — thay vì mỗi plugin tự dựng bảng + trang |

**Tham chiếu:** `vani.cod-reconciliation` (đã có trong plugin-catalog, P2), plugin mẫu `vani.bank-statement` (nhập file sao kê). **Bất biến:** hoàn ≤ đã thu; mọi ghi nhận tiền qua `payment_transactions` unique chống trùng.

### X6 — Khách hàng và CSKH

**Bài toán thật:** tự gắn nhãn "Bom hàng" khi đơn hoàn về lần 2, "VIP" khi chi tiêu 10 triệu/năm; chuyển nhóm khách để hưởng bảng giá thành viên; ghi chú CSKH từ tổng đài vào hồ sơ khách.

| Điểm mở rộng | Loại | Hợp đồng (phác thảo) |
|---|---|---|
| `CustomerSegments` (ghi) | Service contract | `tag(customerId, tag, source)`, `untag`, `assignGroup(customerId, groupId, source)` — có lịch sử; hiện chỉ đọc qua `CustomerGroupDirectory` |
| `CustomerNotes` | Service contract | Ghi chú CSKH theo khách (nguồn plugin/nhân viên), hiện ở thẻ Khách trên trang đơn |

**Tham chiếu:** `vani.loyalty` (spec có sẵn) gắn hạng; `vani.cod-risk-guard` gắn nhãn bom hàng. **Bất biến:** consent và dữ liệu cá nhân theo [security](../15-security/security.md); plugin không đọc SĐT đầy đủ ngoài quyền.

### X7 — Tồn kho: đặt trước và hàng đang về (cần ADR)

**Bài toán thật:** mở bán đặt trước bộ sưu tập mới (giao sau 10–15 ngày), giới hạn số suất; hiện "Hàng đang về" cho khách và nhân viên.

| Điểm mở rộng | Loại | Ghi chú |
|---|---|---|
| `BackorderPolicy` | Contract | Cho phép bán vượt tồn **có giới hạn** theo SKU + ngày dự kiến; đơn đặt trước tách hàng đợi. Đụng bất biến ATS ("strategy chỉ giảm được ATS" — [extension-model §2](../04-extension/extension-model.md)) nên **phải có ADR** trước, cùng cơ chế "không theo dõi tồn" 0.4.48 |
| `IncomingStock` | Service contract | Plugin/ERP ghi số lượng đang về + ngày dự kiến theo SKU; slot PDP và cột lưới tồn (X1) hiển thị |

**Tham chiếu:** `vani.preorder` 1.0.0 — [ADR-047](../19-adr/ADR-047-backorder-incoming-stock.md) duyệt 2026-11-16. Chưa làm (backlog §6b): giao tách phần có hàng, đặt cọc một phần, tự huỷ đơn đặt trước quá hạn.

## 4. Không làm (giữ bất biến)

Như [extension-model §2](../04-extension/extension-model.md): không mở bảng chuyển trạng thái, công thức ATS (trừ X7 sau ADR), làm tròn tổng đơn, kiểm quyền/chữ ký webhook, snapshot đơn, ledger append-only. Thêm cho v3:

- Không có hook **sửa** dữ liệu ở ba chốt — chỉ **chặn** (guard) và **tín hiệu**; quyết định vẫn là lệnh của nhân viên hoặc tự động hoá đi qua cùng lệnh.
- Không cho plugin tự dựng bảng đối soát/hàng đợi riêng khi Core đã có hàng đợi chung (X5, `vani.admin.dashboard.tasks`).
- Không mở rộng làm mất "một chỗ sửa giá" (giá niêm yết sửa ở sản phẩm, 0.4.56).

## 5. Lộ trình

R26: mỗi đợt làm **cùng** plugin tham chiếu (plugin thật trong repo, hoặc mở rộng `vani.hello-world` khi plugin thật chưa đến lượt). Đợt chỉ **thêm** → tăng bản vá Core; đổi contract → tăng số giữa.

| Đợt | Nội dung | Plugin tham chiếu | Ưu tiên |
|---|---|---|---|
| **X1** ✅ 0.4.59 | Màn vận hành mở cho plugin: `adminColumn`/`Filter`/`Action` (+ `Tab` trang đổi trả) và `adminTool` (thay cho slot `list_tools` riêng từng module) cho vận đơn, đổi trả, thanh toán, tồn, bảng giá, khuyến mãi; bộ lọc phụ SkuPicker (`variant`). Vận đơn chưa có trang chi tiết nên chưa có tab | `vani.hello-world` ("Đánh dấu Hello") + `HelloWorldOperationScreensTest`, test trình duyệt `OperationScreensTest` | P1 |
| **X2** ✅ 0.4.60 | `OrderActions`, guard ở bốn lệnh (`vani.order.before_confirm`, `vani.fulfillment.before_push`, `vani.returns.before_approve`, `vani.payment.before_refund`), `OrderRiskSignal` + `OrderRisks`, `ConfirmationChannel` | `vani.cod-risk-guard` 1.0.0 (mới), kênh mẫu của `vani.hello-world` (Zalo OA thật chờ mở lại kết nối ngoài) | P1 |
| **X5** ✅ 0.4.63 | `Payments` mở `collectCod`/`collectCodManually`/`paymentId`; `RefundPayout`; `Reconciliations` + hàng đợi "Đối soát lệch" | `vani.bank-statement` 1.0.0 (mới), kênh chuyển trả mẫu của `vani.hello-world`; `vani.cod-reconciliation` dùng `collectCod` + `Reconciliations` khi làm | P1 |
| **X3** ✅ 0.4.61 | `CarrierAssignment`, `PackingSlipTemplate`, `ReturnPickup` + `ReturnPickups` | `vani.carrier-routing` 1.0.0 (mới), mẫu A6 của `vani.hello-world`; `ReturnPickup` mới có hãng giả trong test — GHN thật chờ mở lại kết nối ngoài | P2 |
| **X4** ✅ 0.4.62 | `BulkPriceMode`, `PriceRounding`, `vani.pricing.bulk.warnings`. **Chưa làm:** giá theo số lượng (`min_qty`) — đổi giá dòng giỏ/snapshot đơn, cần ADR + Owner duyệt | `vani.cost-price` 1.0.0 (mới) | P2 |
| **X6** ✅ 0.4.64 | `CustomerSegments` (ghi: `tag`/`untag`/`assignGroup`), `CustomerNotes` + thẻ "Ghi chú CSKH" trên trang đơn | `vani.cod-risk-guard` 1.1.0 (nhãn `bom-hang` + ghi chú khi đơn hoàn về); `vani.loyalty` dùng khi làm | P2 |
| **X7** ✅ 0.4.65 | `BackorderPolicy`, `Backorders`, `IncomingStock` ([ADR-047](../19-adr/ADR-047-backorder-incoming-stock.md)) | `vani.preorder` 1.0.0 | P3 |

Mỗi đợt: cập nhật [extension-point-catalog](../04-extension/extension-point-catalog.md) (§1–§5), snapshot public API, [CHANGELOG-extension](../04-extension/CHANGELOG-extension.md), contract test trong `Modules\<Ctx>\Testing`, tài liệu plugin trong `docs/05-plugin/contracts`.

## 6. Kiểm chứng

| Bài toán | Viết được không sửa Core khi xong đợt |
|---|---|
| Chấm điểm bom hàng, chặn xác nhận khi rủi ro cao, gắn nhãn khách | X2 + X6 |
| Xác nhận đơn qua Zalo OA / tổng đài | X2 |
| Chặn đẩy vận đơn thiếu khối lượng/sai địa chỉ | X2 |
| Chọn hãng theo tỉnh/COD, phiếu giao A6 có QR, hãng lấy hàng trả | X3 |
| Giá sale theo giá vốn, giá đuôi 9.000, cảnh báo dưới giá vốn, cột biên lợi nhuận | X1 + X4 |
| Tự khớp sao kê chuyển khoản, đối soát COD, chuyển trả tiền qua ngân hàng | X1 + X5 |
| Đặt trước bộ sưu tập mới | X7 (sau ADR) |

Mỗi dòng có một test tích hợp dùng plugin tham chiếu, chạy cùng bộ test Core (như `StaffedCheckpointsTest` cho ADR-046).
