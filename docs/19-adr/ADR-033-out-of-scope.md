# ADR-033 — Phạm vi: những gì VaniShop không làm

- Trạng thái: Accepted · Ngày: 2026-10-10 · Người quyết định: Owner
- Thay thế: ADR-015 (đã xoá). Liên quan: [ADR-001](ADR-001-modular-monolith.md), [ADR-028](ADR-028-single-store-brand-as-catalog.md), [ADR-029](ADR-029-commerce-microkernel.md).

## Context
VaniShop là phần mềm cho **một Owner, một website, một người bán** ([ADR-028](ADR-028-single-store-brand-as-catalog.md)). Tài liệu cũ vẫn mô tả, dù ở trạng thái "đóng băng", một số hướng mở rộng: bán đa người bán, bán qua người có ảnh hưởng, kiến trúc tách service. Chúng kéo thiết kế đi xa mục tiêu: người đọc tưởng phải chừa chỗ (cột `seller_id`, nguồn đơn, scope phân quyền, extension point) cho thứ sẽ không làm.

## Decision
1. **Không nằm trong mục tiêu của dự án** (không phải hoãn, không phải đóng băng):

   | Hạng mục | Gồm |
   |---|---|
   | Microservice | Tách module thành service chạy riêng, giao tiếp qua mạng. Hệ thống là modular monolith ([ADR-001](ADR-001-modular-monolith.md), R19) |
   | Marketplace | Nhiều người bán (seller, commission, settlement, payout); bán trên sàn TMĐT bên ngoài (Shopee, Lazada, TikTok Shop): đồng bộ sản phẩm/tồn lên sàn, chừa tồn cho sàn, kéo đơn từ sàn, nguồn đơn từ sàn |
   | Creator | Người sáng tạo nội dung bán hộ, trang creator, mã/giảm giá theo creator |
   | Affiliate | Tiếp thị liên kết, hoa hồng giới thiệu, ghi nhận nguồn đơn (attribution) cho người giới thiệu |
   | Live Commerce | Bán qua phát trực tiếp |
   | Social commerce | Bán qua nền tảng mạng xã hội như một kênh có tích hợp riêng |

2. Với các hạng mục trên: **không** tài liệu thiết kế, **không** plugin, **không** extension point, cột, bảng, nguồn đơn, scope phân quyền hay ví dụ chuẩn bị cho chúng, trong `docs/` lẫn code.
3. Tên các hạng mục **chỉ xuất hiện trong ADR này**. Test `tests/Architecture/ScopeTest.php` chặn chúng trong `docs/` và code.
4. Mở lại một hạng mục cần ADR mới thay thế ADR này, do Owner quyết định.

### Bổ sung 2026-11-05 — Plugin chỉ phát triển nội bộ (Owner)

5. **Hiện tại cấm** phát triển plugin cho bên thứ ba và **chưa cần plugin SDK**: mọi plugin trong `custom/plugin/` do đội VaniShop viết, review và triển khai cùng mã nguồn ([ADR-026](ADR-026-plugin-deploy-via-code.md)). Khác các hạng mục ở mục 1: đây là quyết định **tạm thời theo nhu cầu**, không phải ngoài mục tiêu vĩnh viễn — được nhắc trong tài liệu, không thuộc danh sách ScopeTest chặn.
   - **Không làm**: gói SDK/khung dự án tách riêng để người ngoài viết plugin, tài liệu "hướng dẫn cho nhà phát triển bên ngoài", cơ chế phân phối/ký số/xác minh plugin của bên khác, sandbox chạy mã không tin cậy, cài plugin qua upload hoặc từ máy chủ ngoài (ADR-026 đã loại).
   - **Vẫn giữ** (phục vụ đội nội bộ): contract, hook, `PluginServiceProvider`, `docs/05-plugin/contracts/`, contract test, snapshot public API + `CHANGELOG-extension` và compatibility policy ([extension-model §5](../04-extension/extension-model.md)) — để plugin nội bộ không vỡ khi Core đổi, và để module Core/plugin tách bạch (R4, R5, R35).
   - Khi thiết kế extension point: tối ưu cho người viết nội bộ (đọc được mã Core, chạy cùng test suite); không thêm lớp tương thích, ổn định API dài hạn hay kiểm soát quyền chỉ để phục vụ bên thứ ba.
   - Mở lại (cho phép plugin bên thứ ba / làm SDK) cần ADR mới do Owner quyết định, kèm đánh giá bảo mật (mã bên ngoài chạy cùng tiến trình, cùng quyền DB).

Không bị ảnh hưởng: nhân viên tạo đơn trong Admin (`source = admin`) cho khách liên hệ qua bất kỳ kênh nào; Zalo Mini App và app dùng Storefront API (`source = zalo`, `app`); campaign và mã giảm giá của cửa hàng.

## Alternatives
- **Giữ ở trạng thái đóng băng** (hiện trạng từ 2026-10-08): vẫn phải đọc, vẫn gợi ý chừa chỗ trong thiết kế. Loại.
- **Gắn nhãn "ngoài phạm vi" trên từng tài liệu cũ**: tài liệu vẫn được tìm thấy và trích dẫn. Loại.

## Consequences
- (+) Tài liệu và extension point chỉ phục vụ một cửa hàng một người bán; không còn thiết kế cho trường hợp giả định.
- (+) Bỏ nguồn đơn `marketplace`; báo cáo kênh bán chỉ còn kênh thật.
- (−) Nếu Owner đổi hướng, phải thiết kế lại từ đầu; bản cũ vẫn còn trong lịch sử git (`docs/13-marketplace/`, ADR-015, roadmap Phase 10).

## Trade-offs
Mất các ghi chú thiết kế sẵn có, đổi lấy phạm vi rõ ràng và Core không mang gánh nặng cho thứ sẽ không làm.
