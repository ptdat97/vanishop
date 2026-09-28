# ADR-018 — Hạ tầng và dữ liệu đặt tại Việt Nam

- Trạng thái: Accepted · Ngày: 2026-09-28 · Người quyết định: Owner

## Context
Dữ liệu cá nhân khách hàng VN (Luật BVDLCN 2025, NĐ 13/2023); khách và đối tác chủ yếu trong nước.

## Problem
Chọn nơi đặt hạ tầng và dữ liệu.

## Decision
- Từ slice Foundation, staging/production và toàn bộ dữ liệu (DB, Redis, object storage, backup, log) đặt tại trung tâm dữ liệu ở **Việt Nam**. Owner chọn trực tiếp nhà cung cấp (Viettel, FPT, VNG, BizFly, CMC…).
- Yêu cầu tối thiểu: managed MySQL 8.4, S3-compatible storage, VM autoscale/Kubernetes, SLA ≥ 99,9%, 2 DC (HN + HCM) cho backup khác vùng, ISO 27001, hỗ trợ 24/7.
- CDN chỉ cache nội dung công khai. SaaS nước ngoài chỉ nhận dữ liệu đã che PII. Không dùng Laravel Cloud cho môi trường thật.
- Docker + Terraform/Ansible để có thể đổi nhà cung cấp.

## Alternatives
Hyperscaler quốc tế: nhiều dịch vụ managed hơn nhưng phải làm hồ sơ chuyển dữ liệu ra nước ngoài.

## Consequences
- (+) Tuân thủ lưu trữ trong nước, độ trễ thấp.
- (−) Cần năng lực DevOps; tự host một số công cụ (Sentry, Meilisearch).

## Trade-offs
Tự vận hành nhiều hơn để đổi lấy tuân thủ và độ trễ thấp.

## Nhà cung cấp đã chọn
_(Owner điền khi chốt.)_
