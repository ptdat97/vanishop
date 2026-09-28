# 0008 — Hạ tầng đặt tại Việt Nam

- Trạng thái: Accepted
- Ngày: 2026-09-28
- Người quyết định: Owner

## Bối cảnh
VaniShop xử lý dữ liệu cá nhân của khách hàng Việt Nam (SĐT, địa chỉ, lịch sử mua). Luật Bảo vệ dữ liệu cá nhân 2025 và NĐ 13/2023 đặt thêm nghĩa vụ khi chuyển dữ liệu ra nước ngoài. Khách hàng và hầu hết đối tác (hãng VC, cổng TT, ERP) đều ở trong nước, nên độ trễ mạng nội địa là lợi thế.

## Quyết định
Từ **Phase 0**, mọi môi trường chạy thật (staging, production) và **toàn bộ dữ liệu** (database, Redis, object storage, backup, log) đặt tại **trung tâm dữ liệu ở Việt Nam**.

- Owner chọn trực tiếp một nhà cung cấp trong nước (Viettel IDC / Viettel Cloud, FPT Cloud, VNG Cloud, BizFly Cloud, CMC Cloud…). Yêu cầu tối thiểu: có **managed MySQL 8.4** và **S3-compatible object storage**, Kubernetes hoặc VM autoscale, SLA ≥ 99,9%, có ít nhất 2 zone/DC (Hà Nội + TP.HCM) cho backup khác vùng, chứng chỉ ISO 27001 / PCI DSS, hỗ trợ 24/7.
- Nếu nhà cung cấp chưa có managed service cho Redis/Meilisearch → tự vận hành trên VM (có runbook, giám sát, backup).
- **CDN/WAF**: ưu tiên CDN có PoP tại VN (VNCDN, Viettel CDN, FPT CDN, hoặc Cloudflare). Nếu CDN nước ngoài chỉ cache nội dung công khai (ảnh, CSS/JS, trang không cá nhân hoá) → không chứa dữ liệu cá nhân; trang/API có dữ liệu cá nhân **không cache** ở CDN.
- Dịch vụ SaaS nước ngoài (Sentry, email SES…): chỉ gửi dữ liệu đã che PII; nếu buộc chứa dữ liệu cá nhân → ưu tiên thay thế trong nước hoặc tự host, hoặc lập hồ sơ chuyển dữ liệu ra nước ngoài.
- **Không** dùng Laravel Cloud cho môi trường thật (có thể tắt `"cloud"` trong `boost.json`).

## Hệ quả
- (+) Đáp ứng yêu cầu lưu trữ dữ liệu trong nước, độ trễ thấp cho khách và đối tác.
- (−) Nhiều dịch vụ managed kém phong phú hơn hyperscaler quốc tế → cần năng lực DevOps (IaC bằng Terraform/Ansible, container hoá bằng Docker).
- (−) Cần đánh giá lại công cụ giám sát/lỗi (Sentry self-host hoặc tương đương).

## Nhà cung cấp đã chọn
_(Owner điền khi chốt.)_
