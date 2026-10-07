<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

/**
 * Service contract (0.3.25): metric vận hành tối thiểu (roadmap Phase 6). Module ghi qua contract này, không phụ thuộc
 * hệ giám sát cụ thể — mặc định ghi vào Laravel Pulse (ADR-032), đổi được sang Prometheus/OTel bằng binding khác.
 *
 * Tên metric là public (dashboard, cảnh báo dựa vào): xem docs/16-observability/observability.md §metric.
 * `$key` là chiều phân nhóm ngắn (mã cổng, mã lỗi, target) — không đưa dữ liệu cá nhân hay id đơn vào.
 * Ghi metric không bao giờ được làm hỏng nghiệp vụ: implementation phải nuốt lỗi của hệ giám sát.
 */
interface Metrics
{
    /** Bộ đếm cộng dồn theo thời gian (vd. orders.created). */
    public function increment(string $name, int $by = 1, string $key = 'all'): void;

    /** Giá trị hiện tại (vd. payments.pending), ghi đè giá trị trước. */
    public function gauge(string $name, int $value, string $key = 'all'): void;
}
