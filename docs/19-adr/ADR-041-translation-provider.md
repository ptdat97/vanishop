# ADR-041 — Dịch máy trong form bản dịch Admin (extension point `TranslationProvider`)

- Trạng thái: **Proposed** · Ngày: 2026-11-05 · Người quyết định: Owner (chờ duyệt)
- Liên quan: [reference-parity-refactor §3.4-5](../20-roadmap/reference-parity-refactor.md), [ADR-038](ADR-038-localized-storefront-urls.md) (bản `en` có URL riêng, bản chưa dịch `noindex`), [ADR-030](ADR-030-extension-surface-v2.md), [ADR-039](ADR-039-admin-ui-reka.md), [commerce-kernel](../02-architecture/commerce-kernel.md) (Core không biết nhà cung cấp cụ thể), R25 (dependency phải duyệt).

## Context
- Cửa hàng có bản tiếng Anh được index riêng (ADR-038). Bản `en` chỉ được index khi có bản dịch (tên không rỗng). Hiện nhân viên gõ tay từng trường ở form bản dịch Admin: sản phẩm (tên, mô tả, chất liệu…), danh mục, bộ sưu tập, thuộc tính, giá trị thuộc tính, màu, loại liên kết, và các form plugin có trường dịch (vd. đầu/chân trang theo schema theme).
- Các form này dùng chung một dạng dữ liệu `translations[locale][field]` (`translationsFor` trong `@admin/locales`).
- Dịch máy cần gọi dịch vụ ngoài (Google Cloud Translation, DeepL, Azure…, hoặc LLM). Mỗi dịch vụ có khoá API, hạn mức, chi phí, điều khoản dữ liệu riêng. Theo microkernel (ADR-029), Core không gắn với nhà cung cấp nào.

## Problem
Giảm công dịch tay mà vẫn giữ: nhân viên duyệt trước khi lưu (chất lượng, thuật ngữ thời trang, tên thương hiệu không dịch); không lộ dữ liệu ngoài phạm vi cần thiết; không thêm dependency hay gọi mạng trong Core; dịch vụ ngoài lỗi hoặc chậm không làm hỏng form.

## Decision
1. **Extension point `Extension\Contracts\TranslationProvider`** (tag `vani.translation_providers`), plugin cung cấp:
   - `code(): string`, `label(): string`.
   - `supports(string $from, string $to): bool`.
   - `translate(TranslationRequest $request): TranslationResult` — **theo lô**: `TranslationRequest {from, to, texts: array<string, string>, format: text|markdown, context?: string}` (khoá = tên trường; `context` vd. "tên sản phẩm thời trang"); `TranslationResult {texts: array<string, string>}` cùng khoá. Ném `TranslationFailed` khi lỗi.
   - Không có contract quản lý khoá/hạn mức: plugin tự khai báo cấu hình bằng `settings()` (khoá API là `secret`).
2. **Core lo phần chung** (`Extension\Application\Translation\MachineTranslator`):
   - Chọn provider: cấu hình `core`/`translation.provider` (mã), không chọn → provider đầu tiên `supports()` cặp ngôn ngữ.
   - Gọi qua `Extensions::call` (circuit breaker, đo thời gian như các lời gọi plugin khác); timeout do plugin đặt (gợi ý ≤ 10 giây); lỗi → trả lỗi có mã cho form, không ném lên người dùng.
   - Giới hạn mỗi lần gọi: tối đa 20 trường, 10.000 ký tự; rate limit theo nhân viên (30 lần/phút).
   - Audit `translation.requested` (provider, cặp ngôn ngữ, số trường, số ký tự — **không** lưu nội dung).
   - Không cache bản dịch, không lưu bản dịch nào xuống DB: kết quả chỉ trả về form.
3. **Endpoint Admin** `POST /{admin}/translations/machine` (`{from, to, fields: {key: text}, format, context}`) → `{fields: {key: text}}`. Quyền: nhân viên có quyền sửa màn hình đang mở (kiểm bằng permission truyền lên, vd. `catalog.manage`); không có provider bật → 404 để giao diện ẩn nút.
4. **Giao diện Admin**: component chung `@admin/Components/MachineTranslate.vue` gắn vào form bản dịch. Khi có ít nhất một provider bật (shared prop `app.translation.available`), nút **"Dịch từ <ngôn ngữ mặc định>"** hiện ở cột ngôn ngữ khác ngôn ngữ mặc định:
   - Chỉ dịch các trường **đang trống** của ngôn ngữ đích (ghi đè cần bấm lần nữa sau khi xác nhận).
   - Kết quả **chỉ điền vào form**, đánh dấu "dịch máy — kiểm tra trước khi lưu"; nhân viên tự bấm Lưu. Không có chế độ dịch hàng loạt nền ở lát đầu.
   - Không dịch: slug (sinh lại từ tên đã dịch như hiện nay), mã, SKU, tên thương hiệu.
5. **Plugin tham chiếu**: một plugin cho một dịch vụ, chọn khi duyệt ADR (vd. `vani.translate-deepl` hoặc `vani.translate-google`), gọi HTTP bằng HTTP client có sẵn của Laravel — **không thêm SDK** (R25). Contract test `Extension\Testing\TranslationProviderContract` (giữ khoá, ném `TranslationFailed` đúng loại, không trả HTML khi `format=text`).
6. **Dữ liệu gửi đi**: chỉ văn bản của các trường đang dịch (nội dung catalog công khai). Không gửi dữ liệu khách, đơn, nhân viên. Ghi rõ trong [security](../15-security/security.md) và cấu hình plugin (điều khoản lưu dữ liệu của nhà cung cấp do Owner chọn).

## Alternatives
- **Dịch tự động khi lưu bản gốc** (không duyệt): nhanh nhưng bản `en` sai sẽ được index ngay (ADR-038 index khi có bản dịch). Loại.
- **Gọi thẳng một dịch vụ trong Core**: gắn Core với nhà cung cấp và khoá API, trái microkernel. Loại.
- **Dịch hàng loạt nền (queue) cho cả catalog**: hữu ích khi nhập nhiều sản phẩm, nhưng cần màn hình duyệt hàng loạt và chi phí khó kiểm soát. Để lát sau, dùng cùng contract.
- **Dùng LLM qua prompt tuỳ ý**: linh hoạt nhưng khó kiểm soát định dạng/chi phí; vẫn làm được như một `TranslationProvider` (plugin) nếu Owner muốn.

## Consequences
- (+) Nhân viên dịch nhanh hơn, vẫn duyệt từng trường; bản `en` chỉ vào index khi người lưu.
- (+) Đổi nhà cung cấp = đổi plugin; Core không có khoá API hay HTTP ra ngoài.
- (−) Chi phí theo ký tự của nhà cung cấp — giới hạn mỗi lần gọi + rate limit + audit số ký tự để theo dõi.
- (−) Thêm một extension point public (snapshot, CHANGELOG-extension) và một component Admin.
- (−) Nội dung catalog được gửi tới bên thứ ba khi nhân viên bấm dịch.

## Trade-offs
Tiết kiệm công dịch tay với chi phí một extension point nhỏ và một lần gọi ra ngoài do người bấm; đổi lấy việc không tự động hoá hoàn toàn — chất lượng bản dịch vẫn do nhân viên chịu trách nhiệm.

## Cần Owner quyết định khi duyệt
1. Duyệt ADR (Proposed → Accepted).
2. Nhà cung cấp cho plugin tham chiếu: DeepL, Google Cloud Translation, hay khác.
3. Có cần dịch hàng loạt nền ở lát đầu không (mặc định: không).
