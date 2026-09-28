# ADR-004 — Extension Points

- Trạng thái: Accepted · Ngày: 2026-09-28

## Context
Plugin cần can thiệp theo nhiều kiểu: thay implementation, phản ứng sự kiện, sửa dữ liệu trong flow, đăng ký UI/cấu hình.

## Problem
Nếu chỉ có một cơ chế (ví dụ chỉ hook) thì hoặc thiếu an toàn (hook sửa mọi thứ) hoặc thiếu linh hoạt.

## Decision
- Bốn cơ chế: **Contract** (thay thế được), **Domain Event** (sau commit), **Hook** filter/action/validate/slot (trong flow, dựa trên `tormjens/eventy`, bọc bởi `Hook`), **Registry** (khai báo).
- Có danh sách điểm **không được mở rộng** (state machine, công thức ATS, Money, scope, snapshot).
- Phân biệt **public** (Contracts, Events, hook `visibility: public`) và **internal**.
- **Compatibility policy** theo SemVer của Core; deprecation tối thiểu 1 minor.
- Danh mục duy nhất: [extension-point-catalog](../04-extension/extension-point-catalog.md).

## Alternatives
- Chỉ dùng Laravel events: không sửa được dữ liệu trong flow.
- Chỉ dùng hook kiểu WordPress: khó kiểm soát invariant và kiểu dữ liệu.

## Consequences
- (+) Mỗi nhu cầu có cơ chế phù hợp; invariant được bảo vệ.
- (−) Phải duy trì registry hook, contract test và changelog extension.

## Trade-offs
Ít tự do hơn "hook mọi nơi", đổi lại nâng cấp an toàn và hành vi dự đoán được.
