<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

/**
 * Kết quả báo cáo: các số tổng (trên cùng), biểu đồ tuỳ chọn, bảng chi tiết (cũng là nội dung file CSV).
 */
final readonly class ReportResult
{
    /**
     * @param  list<Metric>  $summary
     */
    public function __construct(
        public Table $table,
        public array $summary = [],
        public ?Series $chart = null,
    ) {}

    /**
     * @return array{summary: list<array<string, mixed>>, chart: array<string, mixed>|null, table: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'summary' => array_map(fn (Metric $metric): array => $metric->toArray(), $this->summary),
            'chart' => $this->chart?->toArray(),
            'table' => $this->table->toArray(),
        ];
    }
}
