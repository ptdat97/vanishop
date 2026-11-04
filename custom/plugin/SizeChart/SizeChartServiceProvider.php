<?php

declare(strict_types=1);

namespace Plugin\SizeChart;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\PluginServiceProvider;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\SizeChart\Infrastructure\SizeChartEnricher;
use Plugin\SizeChart\Infrastructure\SizeCharts;

/**
 * Bảng size và gợi ý size theo chiều cao/cân nặng. Gán bảng ở form sản phẩm, danh mục, thương hiệu (ưu tiên theo thứ tự
 * đó, rồi bảng mặc định). Đặc tả: docs/05-plugin/specs/size-chart.md.
 */
final class SizeChartServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.size-chart';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        // Chuỗi giao diện storefront của plugin theo ngôn ngữ (khoá = câu tiếng Việt, ADR-038); theme ghi đè được.
        $this->loadJsonTranslationsFrom($this->pluginPath('Resources/lang'));
        $this->permissions(['size_chart.view' => 'Xem bảng size', 'size_chart.manage' => 'Quản lý bảng size']);
        $this->adminMenu('size-charts', 'Bảng size', 'admin.plugins.vani-size-chart.index', 'size_chart.view', 260, group: 'catalog');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('SizeChart', $this->pluginPath('Resources/js/Pages'));

        $this->contribute(StorefrontEnricher::TAG, SizeChartEnricher::class);
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-size-chart');
        $this->onSlot('vani.storefront.pdp.add_to_cart_fields', fn (array $product): ?SlotView => isset($product['extensions'][self::ID])
            ? new SlotView('vani-size-chart::dialog', ['chart' => $product['extensions'][self::ID]])
            : null);

        $this->assignmentSection('product', 'Để trống = theo danh mục, thương hiệu hoặc bảng mặc định.');
        $this->assignmentSection('category', 'Áp dụng cho sản phẩm có danh mục chính là danh mục này hoặc danh mục con. Để trống = theo danh mục cha, thương hiệu hoặc bảng mặc định.');
        $this->assignmentSection('brand', 'Áp dụng cho sản phẩm của thương hiệu khi sản phẩm và danh mục chưa có bảng riêng.');
    }

    private function assignmentSection(string $resource, string $help): void
    {
        $charts = fn (): SizeCharts => $this->app->make(SizeCharts::class);
        // Lựa chọn đọc từ DB khi mở/lưu form (closure — Core 0.3.44), không truy vấn lúc boot.
        $fields = fn (): array => [FieldDefinition::select('chart', 'Bảng size', $charts()->options(), help: $help)];

        $this->adminFormSection($resource, 'chart', 'Bảng size', $fields,
            load: fn (int $id): array => ['chart' => ($chartId = $charts()->assignedChart($resource, $id)) === null ? null : (string) $chartId],
            save: fn (int $id, array $values) => $charts()->assign($resource, $id, ($values['chart'] ?? null) === null || $values['chart'] === '' ? null : (int) $values['chart']),
            order: 300,
        );
    }
}
