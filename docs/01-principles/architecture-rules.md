# Architecture Rules

> Trạng thái: **Designed**. Các rule được kiểm tra tự động (architecture test/CI) sẽ ghi rõ; phần còn lại kiểm tra khi review PR.

Đây là **luật bắt buộc**. PR vi phạm rule chỉ được merge khi có ADR cho phép ngoại lệ.

## A. Core và Plugin

| # | Rule | Kiểm tra |
|---|---|---|
| R1 | **Không sửa Core để phục vụ một business capability duy nhất.** Nếu plugin cần, bổ sung **extension point tổng quát** vào Core ([04-extension](../04-extension/extension-model.md)). | Review |
| R2 | Capability có thể thay đổi độc lập mà **không đổi Commerce invariant** thì làm thành **Plugin** ([commerce-kernel](../02-architecture/commerce-kernel.md)). | Review |
| R3 | Core chỉ chứa commerce primitives, business invariants và extension points. | Review |
| R4 | Phụ thuộc chỉ đi một chiều **Plugin → Core Contracts → Core**. Core **không bao giờ** import `Plugin\`. | Arch test: `arch()->expect('Modules')->not->toUse('Plugin')` |
| R5 | Plugin chỉ dùng API **public** của Core: namespace `Contracts`, `Events`, hook trong registry. Không dùng `Persistence`/`Domain` nội bộ của module. | Arch test |
| R6 | Plugin không được phá invariant: trạng thái đơn chỉ đổi qua state machine; tồn chỉ đổi qua reservation/movement; tiền luôn là `Money`; thao tác luôn qua kiểm tra quyền của Core. | Contract test + review |
| R26 | Mỗi extension point **public** phải có ít nhất **một implementation tham chiếu** (mặc định của Core hoặc plugin trong repo) và, với extension contract, **bộ contract test** trong `Modules\<Ctx>\Testing`. Extension point chưa ai dùng được coi là chưa kiểm chứng. | Review + contract test |
| R27 | **Plugin-first**: capability nghiệp vụ mới mặc định là plugin. Đưa vào Core chỉ khi thoả tiêu chí [commerce-kernel §2](../02-architecture/commerce-kernel.md) và có ADR. | Review + ADR |
| R28 | Core (`modules/`) không chứa **tích hợp nhà cung cấp** (HTTP tới cổng, hãng, SaaS) và không chứa **chính sách kinh doanh/đặc thù thị trường** (COD, chuyển khoản, phí ship, thuế suất quốc gia); những thứ đó là plugin hệ thống hoặc plugin nghiệp vụ ([ADR-029](../19-adr/ADR-029-commerce-microkernel.md)). | Arch test (Designed) + review |
| R29 | Vòng 0 (Shared, Tenancy, Identity, Extension) không phụ thuộc module thương mại nào. | Arch test |

## B. Module và DDD

| # | Rule | Kiểm tra |
|---|---|---|
| R7 | Module chỉ gọi module khác qua `Contracts/` hoặc lắng nghe `Events/`. Không dùng Model, Repository, bảng của module khác. | Arch test |
| R8 | `Domain/` không phụ thuộc framework/hạ tầng (Eloquent, Facade, HTTP, Queue). | Arch test: `expect('Modules\*\Domain')->not->toUse(['Illuminate\Database', 'Illuminate\Support\Facades', 'Illuminate\Http'])` |
| R9 | **Không đặt business logic trong Controller.** Controller chỉ làm ba việc: validate (Form Request), gọi Application service/Action, trả Resource/View. | Review + arch test (controller không dùng `Persistence`) |
| R10 | **Không đặt business logic trong Theme/Storefront/Vue/Blade/CSS.** Giao diện chỉ hiển thị dữ liệu do Application/API trả về. | Review |
| R11 | Mỗi module sở hữu các bảng của mình, và chỉ module đó được ghi vào các bảng này. | Review + [database](../07-database/database.md) |

## C. Dữ liệu và nhất quán

| # | Rule | Kiểm tra |
|---|---|---|
| R12 | **Không gọi ERP/dịch vụ ngoài đồng bộ trong transaction checkout.** Mọi tác vụ ra ngoài đi qua Outbox, sau commit. Ngoại lệ duy nhất: khởi tạo thanh toán (redirect/QR) chạy **sau** khi transaction đặt hàng đã commit. | Review + arch test (Checkout không dùng `Http` client) |
| R13 | Mọi tích hợp ngoài phải **idempotent** (Idempotency-Key, unique event id). | Contract test |
| R14 | **Giữ hàng (reservation) phải atomic**: khoá dòng tồn, kiểm tra ATS và ghi reservation trong một transaction. | Concurrency test |
| R15 | **Order phải giữ snapshot**: tên, SKU, giá, thuế, giảm giá, địa chỉ, thông tin khách, vận chuyển. Không join ngược catalog để hiển thị đơn. | Review + test |
| R16 | **Không dùng FLOAT/DOUBLE/DECIMAL cho tiền.** Tiền là số nguyên minor unit (`BIGINT`) + currency. | CI grep migration + arch test |
| R17 | Ledger (stock movement, loyalty, audit, order events, price history) là **append-only**: không UPDATE, không DELETE. | Review + test |
| R18 | Mỗi Job/Listener phải **idempotent** và chịu được chạy lại. | Review |

## D. Kiến trúc hệ thống

| # | Rule | Kiểm tra |
|---|---|---|
| R19 | **Không biến Modular Monolith thành distributed monolith.** Không tách service chỉ vì "đẹp"; muốn tách phải có ADR kèm số liệu vận hành. Module trong cùng process không gọi nhau bằng HTTP. | ADR |
| R20 | API công khai (Storefront/Admin/Integration) có version; không thay đổi phá vỡ trong cùng version. | Contract test |
| R21 | Không để secret/API key trong source code hay trong log. | CI secret scan |
| R22 | Mọi request, command, event, message tích hợp đều mang **correlation id**. | Test middleware + review |
| R23 | Mã VaniShop không được chứa mã BeikeShop ([clean-room](clean-room-license.md)). | CI grep + audit |

## E. Quy trình

| # | Rule |
|---|---|
| R24 | Tài liệu phản ánh implementation. Cập nhật [status.md](../00-overview/status.md) khi trạng thái thay đổi. |
| R25 | Không thêm dependency khi chưa được phê duyệt (theo AGENTS.md); dependency đã phê duyệt được ghi trong ADR. |
